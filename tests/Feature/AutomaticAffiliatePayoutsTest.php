<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayoutMethod;
use App\Models\Payout;
use App\Services\AutomaticAffiliatePayouts;
use App\Services\AffiliatePayoutSlackNotifier;
use App\Services\RevolutBusinessClient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AutomaticAffiliatePayoutsTest extends TestCase
{
    private FakeRevolutClient $bank;
    private AutomaticAffiliatePayouts $service;
    private Affiliate $affiliate;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'payouts.revolut.source_account_id' => 'source', 'payouts.revolut.environment' => 'sandbox',
            'payouts.slack.channel_id' => 'C0C6XNYD3H6',
            'payouts.slack.admin_url' => 'https://stellarafi.com/affiliate/admin/payouts',
            'services.slack.notifications.bot_user_oauth_token' => 'xoxb-test-token']);
        Http::fake(['slack.com/api/chat.postMessage' => Http::response(['ok' => true, 'ts' => '123.456'])]);
        app('db')->purge('sqlite');
        // Isolated schema avoids unrelated historical data-repair migrations.
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id(); $table->string('status'); $table->string('payout_currency'); $table->timestamps();
        });
        Schema::create('payouts', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('affiliate_id'); $table->decimal('amount', 18, 6);
            $table->string('currency'); $table->string('status'); $table->string('method_type');
            $table->text('method_details_snapshot')->nullable(); $table->string('external_reference')->nullable();
            $table->timestamp('paid_at')->nullable(); $table->timestamps();
        });
        Schema::create('affiliate_payout_methods', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('affiliate_id'); $table->string('type');
            $table->text('data')->nullable(); $table->boolean('is_default'); $table->timestamps();
        });
        (require database_path('migrations/2026_09_08_000001_add_automatic_payout_tracking.php'))->up();
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('affiliate_id'); $table->unsignedBigInteger('payout_id')->nullable();
            $table->decimal('amount', 18, 6); $table->string('currency'); $table->string('status');
            $table->timestamp('approved_at')->nullable(); $table->timestamp('paid_out_at')->nullable();
            $table->timestamp('eligible_payout_at')->nullable(); $table->timestamps();
        });
        Schema::create('affiliate_commission_status_logs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('affiliate_commission_id');
            $table->string('from_status'); $table->string('to_status'); $table->text('note')->nullable(); $table->timestamps();
        });
        $this->travelTo(now()->setDate(2026, 10, 1)->startOfDay());
        $this->affiliate = Affiliate::create(['status' => 'active', 'payout_currency' => 'EUR']);
        $method = new AffiliatePayoutMethod;
        $method->affiliate_id = $this->affiliate->id;
        $method->type = 'revolut_bank'; $method->is_default = true; $method->verified_at = now();
        $method->data = ['environment' => 'sandbox', 'counterparty_id' => 'recipient', 'account_id' => 'destination', 'iban_last_four' => '3000'];
        $method->save();
        $this->bank = new FakeRevolutClient;
        $this->service = new AutomaticAffiliatePayouts($this->bank, new AffiliatePayoutSlackNotifier);
    }

    private function commission(string $amount = '100.005400', string $status = 'approved'): AffiliateCommission
    {
        return AffiliateCommission::create(['affiliate_id' => $this->affiliate->id, 'amount' => $amount,
            'currency' => 'EUR', 'status' => $status, 'eligible_payout_at' => now()->subDay(), 'approved_at' => now()->subDay()]);
    }

    public function test_waits_seven_days_and_creates_one_draft_without_paying(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $this->assertNotNull($payout);
        $this->assertNull($this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable()));
        $this->service->process($payout->id);
        $this->assertCount(0, $this->bank->drafts);
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->service->process($payout->id);
        $this->assertCount(1, $this->bank->drafts);
        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertSame('processing', $payout->fresh()->status);
        $this->assertSame('draft', $payout->fresh()->provider_state);
        $this->assertSame(100.0, $this->bank->drafts[0]['payments'][0]['amount']);
        $this->assertNotNull($payout->fresh()->method_details_snapshot['slack_notified_at'] ?? null);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['channel'] === 'C0C6XNYD3H6'
            && $request['text'] === 'An affiliate payout is ready for finance approval.'
            && ! str_contains(strtolower(json_encode($request->data())), 'revolut'));

        $this->bank->draftExists = false;
        $this->bank->transactionState = 'completed';
        $this->service->process($payout->id);
        $this->assertSame('paid_out', $commission->fresh()->status);
        $this->assertSame('paid', $payout->fresh()->status);
    }

    public function test_subthreshold_pending_and_refund_window_are_excluded(): void
    {
        $this->commission('99.999999');
        $this->commission('1000', 'pending');
        $immature = $this->commission('1000');
        $immature->eligible_payout_at = now()->addDay(); $immature->save();
        $this->assertNull($this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable()));
    }

    public function test_lost_response_is_reconciled_without_resubmitting(): void
    {
        $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $this->travel(7)->days();
        $this->bank->loseResponse = true;
        try { $this->service->process($payout->id); } catch (\RuntimeException) {}
        $this->assertSame('processing', $payout->fresh()->status);
        $this->service->process($payout->id);
        $this->assertCount(1, $this->bank->drafts);
        $this->assertSame('draft', $payout->fresh()->provider_state);
        $this->assertSame('processing', $payout->fresh()->status);
    }

    public function test_rejection_during_hold_stops_payment(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $commission->status = 'rejected'; $commission->save();
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->assertCount(0, $this->bank->drafts);
        $this->assertSame('reserved_commissions_changed', $payout->fresh()->attention_reason);
    }

    public function test_bank_details_are_encrypted_and_hidden_from_serialization(): void
    {
        $method = AffiliatePayoutMethod::first();
        $method->encrypted_details = ['iban' => 'DE89370400440532013000']; $method->save();
        $this->assertStringNotContainsString('DE89370400440532013000', $method->getRawOriginal('encrypted_details'));
        $this->assertArrayNotHasKey('encrypted_details', $method->fresh()->toArray());
        $this->assertSame('DE89370400440532013000', $method->fresh()->encrypted_details['iban']);
    }

    public function test_bank_account_change_restarts_the_seven_day_hold(): void
    {
        $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $method = AffiliatePayoutMethod::first();
        $method->is_default = false; $method->save();
        $replacement = $method->replicate();
        $replacement->is_default = true;
        $replacement->data = array_merge($method->data, ['account_id' => 'new-destination']);
        $replacement->save();
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->assertCount(0, $this->bank->drafts);
        $this->assertTrue($payout->fresh()->scheduled_at->equalTo(now()->addDays(7)));
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->assertSame('new-destination', $this->bank->drafts[0]['payments'][0]['receiver']['account_id']);
    }

    public function test_returned_transfer_reopens_reserved_commissions(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->bank->draftExists = false;
        $this->bank->transactionState = 'reverted';
        $this->service->process($payout->id);
        $this->assertSame('failed', $payout->fresh()->status);
        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertSame($payout->id, $commission->fresh()->payout_id);
        $this->assertCount(1, $this->bank->drafts);
    }
}

class FakeRevolutClient extends RevolutBusinessClient
{
    public array $drafts = [];
    public bool $loseResponse = false;
    public bool $draftExists = true;
    public ?string $transactionState = null;
    public function environment(): string { return 'sandbox'; }
    public function authenticate(): void {}
    public function get(string $path, array $query = []): array { return ['currency' => 'EUR', 'state' => 'active']; }
    public function createPaymentDraft(array $payload): array
    {
        $this->drafts[] = $payload;
        if ($this->loseResponse) { throw new \RuntimeException('Simulated timeout after bank acceptance.'); }
        return ['id' => 'draft-1'];
    }
    public function paymentDraft(string $draftId): ?array
    {
        return $this->draftExists ? ['title' => $this->drafts[0]['title'], 'payments' => []] : null;
    }
    public function paymentDrafts(): array
    {
        return array_map(fn ($draft) => ['id' => 'draft-1', 'title' => $draft['title']], $this->drafts);
    }
    public function transactions(array $query): array
    {
        if (! $this->transactionState || empty($this->drafts)) { return []; }
        $payment = $this->drafts[0]['payments'][0];
        return [[
            'id' => 'transaction', 'type' => 'transfer', 'state' => $this->transactionState,
            'reference' => $payment['reference'],
            'legs' => [['account_id' => $payment['account_id'], 'amount' => -$payment['amount'], 'currency' => 'EUR']],
        ]];
    }
    public function lookup(string $requestId): ?array { return null; }
}

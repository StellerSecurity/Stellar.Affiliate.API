<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayoutMethod;
use App\Models\Payout;
use App\Services\AutomaticAffiliatePayouts;
use App\Services\RevolutBusinessClient;
use Illuminate\Database\Schema\Blueprint;
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
            'payouts.revolut.source_account_id' => 'source', 'payouts.revolut.environment' => 'sandbox']);
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
        $this->service = new AutomaticAffiliatePayouts($this->bank);
    }

    private function commission(string $amount = '100.005400', string $status = 'approved'): AffiliateCommission
    {
        return AffiliateCommission::create(['affiliate_id' => $this->affiliate->id, 'amount' => $amount,
            'currency' => 'EUR', 'status' => $status, 'eligible_payout_at' => now()->subDay(), 'approved_at' => now()->subDay()]);
    }

    public function test_waits_seven_days_and_does_not_pay_twice(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $this->assertNotNull($payout);
        $this->assertNull($this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable()));
        $this->service->process($payout->id);
        $this->assertCount(0, $this->bank->payments);
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->service->process($payout->id);
        $this->assertCount(1, $this->bank->payments);
        $this->assertSame('paid_out', $commission->fresh()->status);
        $this->assertSame('paid', $payout->fresh()->status);
        $this->assertSame(100.0, $this->bank->payments[0]['amount']);
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
        $this->assertCount(1, $this->bank->payments);
        $this->assertSame('paid', $payout->fresh()->status);
    }

    public function test_rejection_during_hold_stops_payment(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $commission->status = 'rejected'; $commission->save();
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->assertCount(0, $this->bank->payments);
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
        $this->assertCount(0, $this->bank->payments);
        $this->assertTrue($payout->fresh()->scheduled_at->equalTo(now()->addDays(7)));
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->assertSame('new-destination', $this->bank->payments[0]['receiver']['account_id']);
    }

    public function test_returned_transfer_reopens_reserved_commissions(): void
    {
        $commission = $this->commission();
        $payout = $this->service->prepare($this->affiliate->id, now()->toDateTimeImmutable());
        $this->travel(7)->days();
        $this->service->process($payout->id);
        $this->bank->lookupState = 'reverted';
        $this->service->process($payout->id);
        $this->assertSame('failed', $payout->fresh()->status);
        $this->assertSame('approved', $commission->fresh()->status);
        $this->assertSame($payout->id, $commission->fresh()->payout_id);
        $this->assertCount(1, $this->bank->payments);
    }
}

class FakeRevolutClient extends RevolutBusinessClient
{
    public array $payments = [];
    public bool $loseResponse = false;
    public string $lookupState = 'completed';
    public function environment(): string { return 'sandbox'; }
    public function authenticate(): void {}
    public function get(string $path, array $query = []): array { return ['currency' => 'EUR', 'state' => 'active']; }
    public function pay(array $payload): array
    {
        $this->payments[] = $payload;
        if ($this->loseResponse) { throw new \RuntimeException('Simulated timeout after bank acceptance.'); }
        return ['id' => 'transaction', 'state' => 'completed'];
    }
    public function lookup(string $requestId): ?array { return ['id' => 'transaction', 'state' => $this->lookupState]; }
}

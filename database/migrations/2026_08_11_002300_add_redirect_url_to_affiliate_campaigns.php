<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('affiliate_campaigns', 'redirect_url')) {
            Schema::table('affiliate_campaigns', function (Blueprint $table): void {
                $table->string('redirect_url', 2048)->nullable()->after('commission_rate');
            });
        }

        DB::table('affiliate_campaigns')
            ->where('product', 'esim')
            ->whereNull('redirect_url')
            ->update(['redirect_url' => 'https://stellarsecurity.com/stellar-esim']);

        DB::table('affiliate_campaigns')
            ->where('product', 'vpn')
            ->whereNull('redirect_url')
            ->update(['redirect_url' => 'https://stellarvpn.org/']);

        DB::table('affiliate_campaigns')
            ->where('product', 'antivirus')
            ->whereNull('redirect_url')
            ->update(['redirect_url' => 'https://stellarsecurity.com/stellar-antivirus']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('affiliate_campaigns', 'redirect_url')) {
            Schema::table('affiliate_campaigns', function (Blueprint $table): void {
                $table->dropColumn('redirect_url');
            });
        }
    }
};

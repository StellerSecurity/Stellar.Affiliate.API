<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('affiliate_commissions')) {
            DB::statement('ALTER TABLE affiliate_commissions MODIFY amount DECIMAL(18,6) NOT NULL');

            // Rebuild every commission from the persisted order amount and rate.
            // DECIMAL(12,2) x DECIMAL(6,4) is exact to six fractional digits.
            DB::statement('UPDATE affiliate_commissions SET amount = order_amount * rate WHERE order_amount IS NOT NULL AND rate IS NOT NULL');
        }

        if (Schema::hasTable('payouts')) {
            DB::statement('ALTER TABLE payouts MODIFY amount DECIMAL(18,6) NOT NULL');
        }

        if (Schema::hasTable('affiliate_commission_correction_logs')) {
            DB::statement('ALTER TABLE affiliate_commission_correction_logs MODIFY old_amount DECIMAL(18,6) NULL');
            DB::statement('ALTER TABLE affiliate_commission_correction_logs MODIFY new_amount DECIMAL(18,6) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('affiliate_commission_correction_logs')) {
            DB::statement('ALTER TABLE affiliate_commission_correction_logs MODIFY old_amount DECIMAL(12,2) NULL');
            DB::statement('ALTER TABLE affiliate_commission_correction_logs MODIFY new_amount DECIMAL(12,2) NULL');
        }

        if (Schema::hasTable('payouts')) {
            DB::statement('ALTER TABLE payouts MODIFY amount DECIMAL(12,2) NOT NULL');
        }

        if (Schema::hasTable('affiliate_commissions')) {
            DB::statement('ALTER TABLE affiliate_commissions MODIFY amount DECIMAL(12,2) NOT NULL');
        }
    }
};

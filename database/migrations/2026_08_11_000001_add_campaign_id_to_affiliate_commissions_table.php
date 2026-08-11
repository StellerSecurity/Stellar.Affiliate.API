<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->unsignedBigInteger('campaign_id')->nullable()->after('affiliate_id');
            $table->index(['campaign_id', 'created_at'], 'affiliate_commissions_campaign_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_commissions', function (Blueprint $table) {
            $table->dropIndex('affiliate_commissions_campaign_created_idx');
            $table->dropColumn('campaign_id');
        });
    }
};

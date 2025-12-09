<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            // Add missing fields
            $table->string('name', 255)->nullable()->after('id');
            $table->string('email', 255)->nullable()->after('name');

            // If you want indexing (not required but recommended)
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', function (Blueprint $table) {
            $table->dropColumn(['name', 'email']);
        });
    }
};

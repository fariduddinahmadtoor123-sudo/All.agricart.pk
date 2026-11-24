<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_mt5_accounts', function (Blueprint $table) {
            $table->decimal('partner_rate', 10, 2)->default(0);
            $table->decimal('customer_rate', 10, 2)->default(0);
            $table->string('deactivated_reason')->nullable();
            $table->timestamp('deactivated_at')->nullable();
        });

        Schema::table('forex_positions', function (Blueprint $table) {
            $table->decimal('partner_commission', 18, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->dropColumn('partner_commission');
        });

        Schema::table('forex_mt5_accounts', function (Blueprint $table) {
            $table->dropColumn(['partner_rate', 'customer_rate', 'deactivated_reason', 'deactivated_at']);
        });
    }
};

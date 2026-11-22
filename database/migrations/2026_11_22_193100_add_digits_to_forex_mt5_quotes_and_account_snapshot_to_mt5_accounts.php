<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forex_mt5_quotes') && ! Schema::hasColumn('forex_mt5_quotes', 'digits')) {
            Schema::table('forex_mt5_quotes', function (Blueprint $table) {
                $table->unsignedTinyInteger('digits')->nullable()->after('spread');
            });
        }

        if (Schema::hasTable('forex_mt5_accounts') && ! Schema::hasColumn('forex_mt5_accounts', 'mt5_balance')) {
            Schema::table('forex_mt5_accounts', function (Blueprint $table) {
                $table->decimal('mt5_balance', 18, 2)->nullable()->after('last_seen_at');
                $table->decimal('mt5_equity', 18, 2)->nullable()->after('mt5_balance');
                $table->decimal('mt5_margin', 18, 2)->nullable()->after('mt5_equity');
                $table->decimal('mt5_free_margin', 18, 2)->nullable()->after('mt5_margin');
                $table->decimal('mt5_profit', 18, 2)->nullable()->after('mt5_free_margin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('forex_mt5_quotes') && Schema::hasColumn('forex_mt5_quotes', 'digits')) {
            Schema::table('forex_mt5_quotes', function (Blueprint $table) {
                $table->dropColumn('digits');
            });
        }

        if (Schema::hasTable('forex_mt5_accounts') && Schema::hasColumn('forex_mt5_accounts', 'mt5_balance')) {
            Schema::table('forex_mt5_accounts', function (Blueprint $table) {
                $table->dropColumn(['mt5_balance', 'mt5_equity', 'mt5_margin', 'mt5_free_margin', 'mt5_profit']);
            });
        }
    }
};

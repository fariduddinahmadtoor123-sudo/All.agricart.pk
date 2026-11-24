<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('forex_mt5_quotes') && ! Schema::hasColumn('forex_mt5_quotes', 'volume_min')) {
            Schema::table('forex_mt5_quotes', function (Blueprint $table) {
                $table->decimal('volume_min', 8, 2)->nullable()->after('digits');
                $table->decimal('volume_step', 8, 2)->nullable()->after('volume_min');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('forex_mt5_quotes') && Schema::hasColumn('forex_mt5_quotes', 'volume_min')) {
            Schema::table('forex_mt5_quotes', function (Blueprint $table) {
                $table->dropColumn(['volume_min', 'volume_step']);
            });
        }
    }
};

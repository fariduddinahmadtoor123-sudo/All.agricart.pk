<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->decimal('entry_price', 18, 8)->nullable()->after('symbol');
            $table->decimal('close_price', 18, 8)->nullable()->after('entry_price');
        });
    }

    public function down(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->dropColumn(['entry_price', 'close_price']);
        });
    }
};

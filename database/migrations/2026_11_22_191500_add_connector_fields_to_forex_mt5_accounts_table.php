<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_mt5_accounts', function (Blueprint $table) {
            $table->string('connector_token', 64)->nullable()->unique()->after('active');
            $table->timestamp('last_seen_at')->nullable()->after('connector_token');
        });

        Schema::create('forex_mt5_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mt5_account_id')->constrained('forex_mt5_accounts')->cascadeOnDelete();
            $table->string('symbol', 32);
            $table->decimal('bid', 18, 8);
            $table->decimal('ask', 18, 8);
            $table->decimal('high', 18, 8)->nullable();
            $table->decimal('low', 18, 8)->nullable();
            $table->decimal('spread', 18, 8)->nullable();
            $table->timestamp('tick_at')->nullable();
            $table->timestamps();

            $table->unique(['mt5_account_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forex_mt5_quotes');

        Schema::table('forex_mt5_accounts', function (Blueprint $table) {
            $table->dropColumn(['connector_token', 'last_seen_at']);
        });
    }
};

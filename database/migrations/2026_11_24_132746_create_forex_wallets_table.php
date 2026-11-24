<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forex_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('balance', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('forex_wallet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('forex_wallets')->cascadeOnDelete();
            $table->foreignId('client_account_id')->nullable()->constrained('forex_client_accounts')->nullOnDelete();
            $table->string('kind');
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('from_place');
            $table->string('to_place');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('forex_ledger_entries', function (Blueprint $table) {
            $table->string('from_place')->nullable();
            $table->string('to_place')->nullable();
        });

        Schema::table('forex_payments', function (Blueprint $table) {
            $table->string('book')->default('account');
        });

        $now = now();

        foreach (DB::table('users')->orderBy('id')->pluck('id') as $userId) {
            DB::table('forex_wallets')->insert([
                'user_id' => $userId,
                'balance' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('forex_payments', function (Blueprint $table) {
            $table->dropColumn('book');
        });

        Schema::table('forex_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['from_place', 'to_place']);
        });

        Schema::dropIfExists('forex_wallet_entries');
        Schema::dropIfExists('forex_wallets');
    }
};

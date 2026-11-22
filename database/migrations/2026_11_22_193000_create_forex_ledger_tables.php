<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_client_accounts', function (Blueprint $table) {
            $table->decimal('balance', 18, 2)->default(0)->after('mt5_account_id');
        });

        Schema::create('forex_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('forex_client_accounts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_account_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forex_ledger_entries');

        Schema::table('forex_client_accounts', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forex_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_account_id')->constrained('forex_client_accounts')->cascadeOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 18, 2);
            $table->string('status', 32);
            $table->string('note')->nullable();
            $table->foreignId('ledger_entry_id')->nullable()->constrained('forex_ledger_entries')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forex_payments');
    }
};

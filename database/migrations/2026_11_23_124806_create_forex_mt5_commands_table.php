<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forex_mt5_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mt5_account_id')->constrained('forex_mt5_accounts')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('forex_positions')->cascadeOnDelete();
            $table->string('action', 16);
            $table->string('status', 16)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('mt5_ticket', 64)->nullable();
            $table->string('error', 255)->nullable();
            $table->timestamps();

            $table->index(['mt5_account_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forex_mt5_commands');
    }
};

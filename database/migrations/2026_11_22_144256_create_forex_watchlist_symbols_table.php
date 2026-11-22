<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forex_watchlist_symbols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('symbol', 32);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'symbol']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forex_watchlist_symbols');
    }
};

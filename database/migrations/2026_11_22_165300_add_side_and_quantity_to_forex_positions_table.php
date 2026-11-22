<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->string('side')->nullable()->after('symbol');
            $table->decimal('quantity', 12, 2)->nullable()->after('side');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->dropColumn(['side', 'quantity']);
        });
    }
};

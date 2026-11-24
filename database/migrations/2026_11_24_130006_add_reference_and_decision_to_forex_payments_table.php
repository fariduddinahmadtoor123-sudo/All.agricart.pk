<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_payments', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('note');
            $table->string('rejection_reason')->nullable()->after('reference');
            $table->foreignId('decided_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('forex_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('decided_by');
            $table->dropColumn(['reference', 'rejection_reason']);
        });
    }
};

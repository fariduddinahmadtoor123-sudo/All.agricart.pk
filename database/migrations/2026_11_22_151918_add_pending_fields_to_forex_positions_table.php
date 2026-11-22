<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->string('status', 16)->default('open')->after('side');
            $table->string('order_type', 16)->default('market')->after('status');
            $table->decimal('trigger_price', 18, 8)->nullable()->after('entry_price');
        });

        DB::table('forex_positions')->whereNotNull('closed_at')->update(['status' => 'closed']);
        DB::table('forex_positions')->whereNull('closed_at')->update(['status' => 'open']);
    }

    public function down(): void
    {
        Schema::table('forex_positions', function (Blueprint $table) {
            $table->dropColumn(['status', 'order_type', 'trigger_price']);
        });
    }
};

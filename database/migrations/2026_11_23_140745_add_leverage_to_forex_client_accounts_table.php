<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_client_accounts', function (Blueprint $table) {
            $table->unsignedSmallInteger('leverage')->default(100)->after('balance');
        });
    }

    public function down(): void
    {
        Schema::table('forex_client_accounts', function (Blueprint $table) {
            $table->dropColumn('leverage');
        });
    }
};

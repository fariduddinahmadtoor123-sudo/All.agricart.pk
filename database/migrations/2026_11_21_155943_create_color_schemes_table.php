<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('color_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('page');
            $table->string('card');
            $table->string('soft');
            $table->string('line');
            $table->string('primary');
            $table->timestamps();
        });

        $now = now();

        DB::table('color_schemes')->insert([
            ['name' => 'Sand', 'page' => '#f5ebe0', 'card' => '#edede9', 'soft' => '#e3d5ca', 'line' => '#d6ccc2', 'primary' => '#d5bdaf', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Forest', 'page' => '#f3f6ee', 'card' => '#fbfcf8', 'soft' => '#e4efd2', 'line' => '#c9d8b2', 'primary' => '#83b735', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Jade', 'page' => '#eef3ef', 'card' => '#f7f6f2', 'soft' => '#d7e6dc', 'line' => '#b7cdc2', 'primary' => '#6e9784', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Stone', 'page' => '#f3f2ef', 'card' => '#fafaf8', 'soft' => '#e6e3dc', 'line' => '#d0cbc2', 'primary' => '#a89884', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('color_schemes');
    }
};

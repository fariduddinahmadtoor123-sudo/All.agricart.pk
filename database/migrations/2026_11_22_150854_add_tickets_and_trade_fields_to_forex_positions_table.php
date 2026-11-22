<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('forex_positions')) {
            return;
        }

        Schema::table('forex_positions', function (Blueprint $table) {
            if (! Schema::hasColumn('forex_positions', 'desk_ticket')) {
                $table->string('desk_ticket', 32)->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('forex_positions', 'mt5_ticket')) {
                $table->string('mt5_ticket', 64)->nullable()->after('desk_ticket');
            }

            if (! Schema::hasColumn('forex_positions', 'client_account_id')) {
                $table->foreignId('client_account_id')->nullable()->after('user_id')->constrained('forex_client_accounts')->nullOnDelete();
            }

            if (! Schema::hasColumn('forex_positions', 'stop_loss')) {
                $table->decimal('stop_loss', 18, 8)->nullable()->after('close_price');
            }

            if (! Schema::hasColumn('forex_positions', 'take_profit')) {
                $table->decimal('take_profit', 18, 8)->nullable()->after('stop_loss');
            }

            if (! Schema::hasColumn('forex_positions', 'commission')) {
                $table->decimal('commission', 12, 2)->default(0)->after('take_profit');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('forex_positions')) {
            return;
        }

        Schema::table('forex_positions', function (Blueprint $table) {
            if (Schema::hasColumn('forex_positions', 'client_account_id')) {
                $table->dropConstrainedForeignId('client_account_id');
            }

            foreach (['desk_ticket', 'mt5_ticket', 'stop_loss', 'take_profit', 'commission'] as $column) {
                if (Schema::hasColumn('forex_positions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

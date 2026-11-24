<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forex_ledger_entries', function (Blueprint $table) {
            $table->string('desk_ticket', 32)->nullable()->after('note');
            $table->string('mt5_ticket', 64)->nullable()->after('desk_ticket');
        });

        $positions = DB::table('forex_positions')
            ->whereNotNull('client_account_id')
            ->whereNotNull('mt5_ticket')
            ->where('mt5_ticket', '!=', '')
            ->get();

        foreach ($positions as $position) {
            $already = DB::table('forex_ledger_entries')
                ->where('client_account_id', $position->client_account_id)
                ->where('mt5_ticket', $position->mt5_ticket)
                ->exists();

            if ($already) {
                continue;
            }

            $balance = DB::table('forex_client_accounts')->where('id', $position->client_account_id)->value('balance');
            $desk = is_string($position->desk_ticket) && $position->desk_ticket !== ''
                ? $position->desk_ticket
                : 'D-'.$position->id;

            DB::table('forex_ledger_entries')->insert([
                'client_account_id' => $position->client_account_id,
                'type' => 'trade',
                'amount' => 0,
                'balance_after' => $balance ?? 0,
                'note' => sprintf(
                    'Open %s %s %s %.2f lots',
                    $desk,
                    $position->side ?? 'trade',
                    $position->symbol,
                    (float) $position->quantity,
                ),
                'desk_ticket' => $desk,
                'mt5_ticket' => $position->mt5_ticket,
                'created_by' => $position->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('forex_ledger_entries', function (Blueprint $table) {
            $table->dropColumn(['desk_ticket', 'mt5_ticket']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('id')->constrained('roles')->nullOnDelete();
            $table->string('status', 20)->default('active')->after('password');
            $table->string('name_ur')->nullable()->after('name');
            $table->text('address')->nullable()->after('email');
            $table->text('address_ur')->nullable()->after('address');
            $table->json('phone_numbers')->nullable()->after('address_ur');
            $table->json('bank_accounts')->nullable()->after('phone_numbers');
            $table->string('profile_photo_path')->nullable()->after('bank_accounts');
            $table->string('cnic_front_path')->nullable()->after('profile_photo_path');
            $table->string('cnic_back_path')->nullable()->after('cnic_front_path');
            $table->string('rejection_reason')->nullable()->after('cnic_back_path');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
        });

        $now = now();
        $roleId = DB::table('roles')->where('name', 'Super admin')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Super admin',
                'description' => 'Approves people and assigns their role.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('users')->where('email', 'admin@gmail.com')->update([
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn([
                'status',
                'name_ur',
                'address',
                'address_ur',
                'phone_numbers',
                'bank_accounts',
                'profile_photo_path',
                'cnic_front_path',
                'cnic_back_path',
                'rejection_reason',
                'rejected_at',
            ]);
        });
    }
};

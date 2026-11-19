<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Adds the "admin" role and creates the first admin account.
 * The admin manages user accounts (create, update, delete).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','librarian','faculty','student') NOT NULL");

        if (! DB::table('users')->where('role', 'admin')->exists()) {
            DB::table('users')->insert([
                'name' => 'System Administrator',
                'id_number' => 'ADMIN-0001',
                'email' => 'admin@csu.edu.ph',
                'role' => 'admin',
                'is_active' => true,
                'password' => Hash::make('admin12345'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->delete();
        DB::statement("ALTER TABLE users MODIFY role ENUM('librarian','faculty','student') NOT NULL");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Get all users from the database
        $users = DB::table('users')->get();

        foreach ($users as $user) {
            // Check if password is already hashed (bcrypt hashes start with $2y$)
            if (!str_starts_with($user->password, '$2y$')) {
                // Hash the plaintext password
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'password' => Hash::make($user->password),
                        'updated_at' => now()
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cannot reverse password hashing as original passwords are lost
        // This is intentional for security reasons
    }
};

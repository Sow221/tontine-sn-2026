<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword  = env('SEED_ADMIN_PASSWORD',  'Admin2024!');
        $membrePassword = env('SEED_MEMBRE_PASSWORD', 'Membre2024!');

        $membreTest1 = User::create([
            'email' => 'fatou@tontinesn.test', 'name' => 'Fatou Diallo',
            'phone_number' => '+221 77 111 22 33', 'password' => bcrypt($membrePassword),
            'role' => 'member', 'kyc_verified' => true, 'is_active' => true,
            'created_at' => now()->subMonths(6),
        ]);

        $membreTest2 = User::create([
            'email' => 'membre@tontinesn.test', 'name' => 'Moussa Ndiaye',
            'phone_number' => '+221 76 444 55 66', 'password' => bcrypt($membrePassword),
            'role' => 'member', 'kyc_verified' => false, 'is_active' => true,
            'created_at' => now()->subMonths(5),
        ]);

        User::create([
            'email' => 'admin@tontinesn.test', 'name' => 'Administrateur',
            'phone_number' => '+221 77 000 00 00', 'password' => bcrypt($adminPassword),
            'role' => 'super_admin', 'kyc_verified' => true, 'is_active' => true,
            'created_at' => now()->subMonths(6),
        ]);

        $this->call(TontineSeeder::class, false, [
            'membreTest1' => $membreTest1,
            'membreTest2' => $membreTest2,
        ]);

        $this->call(BadgeSeeder::class);
    }
}

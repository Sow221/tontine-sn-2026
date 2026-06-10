<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TontineSeeder extends Seeder
{
    private User $membreTest1;

    private User $membreTest2;

    public function run(
        User $membreTest1,
        User $membreTest2
    ): void {
        $this->membreTest1 = $membreTest1;
        $this->membreTest2 = $membreTest2;

        $tontine = Tontine::create([
            'name' => 'Tontine Sandaga',
            'code' => 'SAN001',
            'description' => 'Tontine mensuelle — Tontine Sandaga.',
            'amount' => 25000,
            'frequency' => 'monthly',
            'type' => 'fixed',
            'status' => 'pending',
            'start_date' => Carbon::now()->addWeek(),
            'max_members' => 8,
            'penalty_rate' => 0,
            'draw_method' => 'sequential',
            'created_by' => $membreTest1->id,
        ]);

        $tontine->members()->attach($membreTest1->id, [
            'status' => 'active', 'position' => 1, 'joined_at' => now()->subDays(7),
        ]);

        $tontine->members()->attach($membreTest2->id, [
            'status' => 'pending', 'position' => 2, 'joined_at' => now()->subDays(1),
        ]);

        $total = [User::count(), Tontine::count(), Cycle::count()];

        $this->command->info('');
        $this->command->info('  Compte       Email');
        $this->command->info('  ---------    -----------------------');
        $this->command->info('  Super Admin  admin@tontinesn.test');
        $this->command->info('  Membre 1     fatou@tontinesn.test');
        $this->command->info('  Membre 2     membre@tontinesn.test');
        $this->command->info('');
        $this->command->warn('⚠️  Changez les mots de passe des comptes de test en production (voir .env : SEED_ADMIN_PASSWORD, SEED_MEMBRE_PASSWORD).');
        $this->command->info("  {$total[0]} users · {$total[1]} tontine · {$total[2]} cycles");
    }
}

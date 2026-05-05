<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tontine;
use App\Models\Cycle;
use App\Models\Transaction;
use App\Models\CreditScore;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('transactions')->truncate();
        DB::table('cycles')->truncate();
        DB::table('tontine_members')->truncate();
        DB::table('tontines')->truncate();
        DB::table('credit_scores')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── Utilisateurs ──────────────────────────────────────────────────

        $superAdmin = User::create([
            'email'              => 'awas28948@gmail.com',
            'name'               => 'Super Admin',
            'role'               => 'super_admin',
            'kyc_verified'       => true,
            'preferred_language' => 'fr',
            'is_active'          => true,
        ]);

        $manager = User::create([
            'email'              => 'awas28948+manager@gmail.com',
            'name'               => 'Fatou Diallo',
            'role'               => 'manager',
            'kyc_verified'       => true,
            'preferred_language' => 'wo',
            'is_active'          => true,
        ]);

        $members = collect([
            ['email' => 'awas28948+aminata@gmail.com',  'name' => 'Aminata Sow'],
            ['email' => 'awas28948+moussa@gmail.com',   'name' => 'Moussa Ndiaye'],
            ['email' => 'awas28948+rokhaya@gmail.com',  'name' => 'Rokhaya Mbaye'],
            ['email' => 'awas28948+ibrahima@gmail.com', 'name' => 'Ibrahima Fall'],
        ])->map(fn($data) => User::create([
            ...$data,
            'role'               => 'member',
            'kyc_verified'       => false,
            'preferred_language' => 'fr',
            'is_active'          => true,
        ]));

        // ── Tontine ───────────────────────────────────────────────────────

        $tontine = Tontine::create([
            'name'        => 'Tontine des femmes du marché Sandaga',
            'code'        => 'SAND01',
            'description' => 'Tontine mensuelle pour les commerçantes du marché Sandaga.',
            'amount'      => 25000,
            'frequency'   => 'monthly',
            'type'        => 'fixed',
            'status'      => 'active',
            'start_date'  => Carbon::now()->startOfMonth(),
            'max_members' => 10,
            'penalty_rate'=> 5,
            'draw_method' => 'sequential',
            'created_by'  => $manager->id,
        ]);

        $allMembers = collect([$manager])->merge($members);
        $allMembers->each(function ($user, $i) use ($tontine) {
            $tontine->members()->attach($user->id, [
                'status'    => 'active',
                'position'  => $i + 1,
                'joined_at' => now(),
            ]);
        });

        // ── Cycles ────────────────────────────────────────────────────────

        $date = Carbon::now()->startOfMonth();
        for ($i = 1; $i <= 5; $i++) {
            $beneficiary = $allMembers->get($i - 1);
            $isPast      = $date->isPast();

            $cycle = Cycle::create([
                'tontine_id'      => $tontine->id,
                'cycle_number'    => $i,
                'beneficiary_id'  => $isPast ? $beneficiary->id : null,
                'due_date'        => $date->copy(),
                'status'          => $isPast ? 'paid' : ($i === 3 ? 'partial' : 'pending'),
                'total_collected' => $isPast ? 25000 * $allMembers->count() : 0,
                'drawn_at'        => $isPast ? $date->copy() : null,
            ]);

            if ($isPast) {
                $allMembers->each(function ($user) use ($cycle) {
                    Transaction::create([
                        'cycle_id'           => $cycle->id,
                        'user_id'            => $user->id,
                        'amount'             => 25000,
                        'method'             => collect(['wave', 'orange_money', 'cash'])->random(),
                        'external_reference' => 'REF-' . uniqid(),
                        'status'             => 'success',
                        'paid_at'            => now()->subDays(rand(1, 5)),
                    ]);
                });
            }

            $date->addMonth();
        }

        // ── Scores crédit ─────────────────────────────────────────────────

        $allMembers->each(function ($user) {
            CreditScore::create([
                'user_id'           => $user->id,
                'score'             => round(rand(40, 90) / 10, 1),
                'total_contributed' => rand(50000, 500000),
                'on_time_payments'  => rand(3, 10),
                'total_cycles'      => rand(5, 12),
                'seniority_months'  => rand(1, 24),
                'badge'             => collect(['none', 'bronze', 'silver', 'gold'])->random(),
                'calculated_at'     => now(),
            ]);
        });

        $this->command->info('✅ Données de test insérées avec succès.');
        $this->command->info('   Super Admin : awas28948@gmail.com');
        $this->command->info('   Gérante     : awas28948+manager@gmail.com');
        $this->command->info('   Membres     : awas28948+aminata@gmail.com, etc.');
    }
}

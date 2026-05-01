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
            'phone_number'      => '+221700000001',
            'name'              => 'Super Admin',
            'email'             => 'admin@tontinesn.sn',
            'role'              => 'super_admin',
            'kyc_verified'      => true,
            'preferred_language'=> 'fr',
            'is_active'         => true,
        ]);

        $manager = User::create([
            'phone_number'      => '+221770000002',
            'name'              => 'Fatou Diallo',
            'role'              => 'manager',
            'kyc_verified'      => true,
            'preferred_language'=> 'wo',
            'is_active'         => true,
        ]);

        $members = collect([
            ['phone_number' => '+221780000003', 'name' => 'Aminata Sow'],
            ['phone_number' => '+221760000004', 'name' => 'Moussa Ndiaye'],
            ['phone_number' => '+221750000005', 'name' => 'Rokhaya Mbaye'],
            ['phone_number' => '+221770000006', 'name' => 'Ibrahima Fall'],
        ])->map(fn($data) => User::create([
            ...$data,
            'role'              => 'member',
            'kyc_verified'      => false,
            'preferred_language'=> 'fr',
            'is_active'         => true,
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

        // Membres
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
                'tontine_id'     => $tontine->id,
                'cycle_number'   => $i,
                'beneficiary_id' => $isPast ? $beneficiary->id : null,
                'due_date'       => $date->copy(),
                'status'         => $isPast ? 'paid' : ($i === 3 ? 'partial' : 'pending'),
                'total_collected'=> $isPast ? 25000 * $allMembers->count() : 0,
                'drawn_at'       => $isPast ? $date->copy() : null,
            ]);

            // Transactions pour cycles passés
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
                'user_id'          => $user->id,
                'score'            => round(rand(40, 90) / 10, 1),
                'total_contributed'=> rand(50000, 500000),
                'on_time_payments' => rand(3, 10),
                'total_cycles'     => rand(5, 12),
                'seniority_months' => rand(1, 24),
                'badge'            => collect(['none', 'bronze', 'silver', 'gold'])->random(),
                'calculated_at'    => now(),
            ]);
        });

        $this->command->info('✅ Données de test insérées avec succès.');
        $this->command->info('   Super Admin : +221700000001');
        $this->command->info('   Gérante     : +221770000002');
    }
}

<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuctionBid;
use App\Models\ChatMessage;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditScoringService;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DemoDataSeeder extends Seeder
{
    private Carbon $now;

    private array $users = [];

    public function run(): void
    {
        $this->now = now();
        $this->command?->info('=== DONNÉES DÉMO STRATÉGIQUES ===');

        $this->loadUsers();

        $this->createFamilleDiallo();
        $this->createAmisThies();
        $this->createMedina();
        $this->createDakarPlateau();
        $this->createEncheresLiberte();
        $this->createEncheresTeranga();
        $this->createEpargneHlm();
        $this->createEpargneCite();
        $this->createCeremonialMariage();
        $this->createCastorsTirage();
        $this->createTontineSuspendue();

        $this->createKycDocuments();
        $this->createP2PTransactions();
        $this->createChatMessages();
        $this->awardBadgesAndScores();

        $this->command?->info('✓ TERMINÉ — Toutes les données démo sont prêtes.');
    }

    private function loadUsers(): void
    {
        $emails = [
            'admin@tontinesn.test',
            'fatou@tontinesn.test',
            'membre@tontinesn.test',
            'manager@tontinesn.test',
            'tessier@tontinesn.test',
            'rbousquet@tontinesn.test',
            'nbonnin@tontinesn.test',
            'jlemonnier@tontinesn.test',
            'hugues@tontinesn.test',
            'pierre@tontinesn.test',
            'maryse@tontinesn.test',
            'ilaurent@tontinesn.test',
            'hcarre@tontinesn.test',
            'roland@tontinesn.test',
            'npaul@tontinesn.test',
            'bleroy@tontinesn.test',
            'juliette@tontinesn.test',
            'maurice@tontinesn.test',
            'lacombe.franck@tontinesn.test',
            'diaz.salome@tontinesn.test',
            'gilles.guillaume@tontinesn.test',
            'renaud.nicolas@tontinesn.test',
            'leroux.alexandre@tontinesn.test',
            'garnier.alphonse@tontinesn.test',
            'leclerc.sabine@tontinesn.test',
            'fournier.amedee@tontinesn.test',
            'menard.chantal@tontinesn.test',
            'morin.sylvie@tontinesn.test',
            'guerin.marc@tontinesn.test',
        ];
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->users[$email] = $user;
            }
        }
        $this->command?->info('  ✓ '.count($this->users).' utilisateurs chargés');
    }

    private function user(string $email): ?User
    {
        return $this->users[$email] ?? null;
    }

    // ──────────────────────────────────────────────
    //  Tontine 1 : Famille Diallo — Fixed, Monthly
    //  6 cycles, 8 membres, active, montant 25 000
    // ──────────────────────────────────────────────
    private function createFamilleDiallo(): void
    {
        $this->command?->info("\n--- 1. Famille Diallo (fixe, 25 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'FAM001'],
            [
                'name' => 'Tontine Famille Diallo',
                'description' => 'Tontine mensuelle de la famille Diallo — 8 membres, 25 000 FCFA par tour.',
                'amount' => 25000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(5),
                'max_members' => 8, 'quorum' => 75, 'penalty_rate' => 10,
                'draw_method' => 'sequential',
                'created_by' => $this->user('admin@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['admin@tontinesn.test', 'fatou@tontinesn.test', 'membre@tontinesn.test',
            'tessier@tontinesn.test', 'nbonnin@tontinesn.test', 'hugues@tontinesn.test',
            'jlemonnier@tontinesn.test', 'pierre@tontinesn.test'];
        $this->attachMembers($t, $members);

        // 5 cycles terminés (mois 1-5), 1 cycle en cours (mois 6)
        for ($i = 1; $i <= 6; $i++) {
            $benef = $this->user($members[$i - 1]);
            $due = $this->now->copy()->subMonths(6 - $i);
            $drawn = $this->now->copy()->subMonths(6 - $i)->addDays(3);
            $isCurrent = ($i === 6);
            $status = $isCurrent ? 'partial' : 'paid';

            $c = $this->makeCycle($t, $i, $status, $due, $benef,
                $isCurrent ? null : $drawn);

            foreach ($members as $email) {
                if ($isCurrent && $email === 'pierre@tontinesn.test') {
                    continue;
                }

                $isLate = ($email === 'membre@tontinesn.test' && $i === 4);
                Transaction::create([
                    'cycle_id' => $c->id, 'user_id' => $this->user($email)->id,
                    'amount' => 25000, 'method' => 'wave', 'status' => 'success',
                    'paid_at' => $isLate ? $due->copy()->addDays(4) : $due->copy()->subDay(),
                    'type' => 'cycle_payment',
                ]);
            }
        }
        $this->command?->info('  ✓ 6 cycles (5 payés + 1 partiel) · 8 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 2 : Amis Thiès — Fixed, Weekly
    //  12 cycles hebdo, 5 membres, 10 000 F
    // ──────────────────────────────────────────────
    private function createAmisThies(): void
    {
        $this->command?->info("\n--- 2. Amis Thiès (fixe hebdo, 10 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'THI001'],
            [
                'name' => 'Tontine Amis Thiès',
                'description' => 'Tontine hebdomadaire entre amis à Thiès — 10 000 FCFA/semaine.',
                'amount' => 10000, 'frequency' => 'weekly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subWeeks(12),
                'max_members' => 5, 'quorum' => 60, 'penalty_rate' => 5,
                'draw_method' => 'sequential',
                'created_by' => $this->user('manager@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['manager@tontinesn.test', 'tessier@tontinesn.test', 'rbousquet@tontinesn.test',
            'npaul@tontinesn.test', 'bleroy@tontinesn.test'];
        $this->attachMembers($t, $members);

        for ($i = 1; $i <= 12; $i++) {
            $benef = $this->user($members[($i - 1) % count($members)]);
            $due = $this->now->copy()->subWeeks(12 - $i);
            $drawn = $this->now->copy()->subWeeks(12 - $i)->addDay();

            // Semaines 11-12 : overdue (nobody drew yet)
            $isOverdue = ($i >= 11);
            $status = $isOverdue ? 'overdue' : 'paid';

            $c = $this->makeCycle($t, $i, $status, $due, $isOverdue ? null : $benef,
                $isOverdue ? null : $drawn);

            foreach ($members as $email) {
                if ($isOverdue) {
                    // Semaine 11 : 3/5 payés, semaine 12 : 2/5 payés
                    $payCount = ($i === 11) ? 3 : 2;
                    if (array_search($email, $members, true) >= $payCount) {
                        continue;
                    }
                }

                $isLate = ($email === 'bleroy@tontinesn.test' && $i === 8);
                Transaction::create([
                    'cycle_id' => $c->id, 'user_id' => $this->user($email)->id,
                    'amount' => 10000, 'method' => 'wave', 'status' => 'success',
                    'paid_at' => $isLate ? $due->copy()->addDays(2) : $due->copy()->subDay(),
                    'type' => 'cycle_payment',
                ]);
            }
        }
        $this->command?->info('  ✓ 12 cycles (10 payés + 2 overdue) · 5 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 3 : Médina — Fixed, Monthly, Petite
    //  1 cycle payé + 1 en cours, 3 membres, 15 000
    // ──────────────────────────────────────────────
    private function createMedina(): void
    {
        $this->command?->info("\n--- 3. Médina (fixe, petite, 15 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'MED001'],
            [
                'name' => 'Tontine Médina',
                'description' => 'Petite tontine de quartier à Médina — 3 mamans, 15 000 FCFA/mois.',
                'amount' => 15000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(2),
                'max_members' => 3, 'quorum' => 60, 'penalty_rate' => 10,
                'draw_method' => 'sequential',
                'created_by' => $this->user('juliette@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['fatou@tontinesn.test', 'juliette@tontinesn.test', 'maurice@tontinesn.test'];
        $this->attachMembers($t, $members);

        // Cycle 1 : payé
        $c1 = $this->makeCycle($t, 1, 'paid',
            $this->now->copy()->subMonths(2), $this->user('fatou@tontinesn.test'),
            $this->now->copy()->subMonths(2)->addDays(3));
        $this->payAll($c1, $members, 15000, $this->now->copy()->subMonths(2)->subDay(), 'free_money');

        // Cycle 2 : en cours
        $c2 = $this->makeCycle($t, 2, 'partial',
            $this->now->copy()->subMonth(), $this->user('juliette@tontinesn.test'),
            $this->now->copy()->subMonth()->addDays(2));
        foreach (['fatou@tontinesn.test', 'juliette@tontinesn.test'] as $email) {
            Transaction::create([
                'cycle_id' => $c2->id, 'user_id' => $this->user($email)->id,
                'amount' => 15000, 'method' => 'wave', 'status' => 'success',
                'paid_at' => $this->now->copy()->subMonth()->subDay(),
                'type' => 'cycle_payment',
            ]);
        }

        $this->command?->info('  ✓ 2 cycles (1 payé + 1 partiel) · 3 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 4 : Dakar Plateau — Fixed, Completed
    //  6 cycles terminés, 6 membres, 50 000 F
    // ──────────────────────────────────────────────
    private function createDakarPlateau(): void
    {
        $this->command?->info("\n--- 4. Dakar Plateau (terminée, 50 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'DAK001'],
            [
                'name' => 'Tontine Dakar Plateau',
                'description' => 'Tontine de 6 mois terminée avec succès — 6 professionnels du Plateau.',
                'amount' => 50000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'completed', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(6),
                'end_date' => $this->now->copy()->subMonth(),
                'max_members' => 6, 'quorum' => 80, 'penalty_rate' => 15,
                'draw_method' => 'sequential',
                'created_by' => $this->user('admin@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['admin@tontinesn.test', 'fatou@tontinesn.test', 'membre@tontinesn.test',
            'tessier@tontinesn.test', 'rbousquet@tontinesn.test', 'nbonnin@tontinesn.test'];
        $this->attachMembers($t, $members);

        for ($i = 1; $i <= 6; $i++) {
            $benef = $this->user($members[$i - 1]);
            $due = $this->now->copy()->subMonths(7 - $i);
            $c = $this->makeCycle($t, $i, 'paid', $due, $benef,
                $this->now->copy()->subMonths(7 - $i)->addDays(3));

            foreach ($members as $email) {
                $isLate = ($email === 'rbousquet@tontinesn.test' && $i === 3) ||
                          ($email === 'membre@tontinesn.test' && $i === 5);
                Transaction::create([
                    'cycle_id' => $c->id, 'user_id' => $this->user($email)->id,
                    'amount' => 50000, 'method' => 'orange_money', 'status' => 'success',
                    'paid_at' => $isLate ? $due->copy()->addDays(3) : $due->copy()->subDay(),
                    'type' => 'cycle_payment',
                ]);
            }
        }
        $this->command?->info('  ✓ 6 cycles terminés · 6 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 5 : Enchères Liberté — Auction
    //  3 cycles avec enchères, 5 membres, 30 000 F
    // ──────────────────────────────────────────────
    private function createEncheresLiberte(): void
    {
        $this->command?->info("\n--- 5. Enchères Liberté (auction, 30 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'ENC001'],
            [
                'name' => 'Tontine Enchères Liberté',
                'description' => 'Tontine aux enchères du quartier Liberté — enchérissez pour recevoir en priorité.',
                'amount' => 30000, 'frequency' => 'monthly', 'type' => 'auction',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(3),
                'max_members' => 5, 'quorum' => 1, 'penalty_rate' => 10,
                'draw_method' => 'random', 'weighted_draw' => true,
                'created_by' => $this->user('rbousquet@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['rbousquet@tontinesn.test', 'npaul@tontinesn.test', 'bleroy@tontinesn.test',
            'gilles.guillaume@tontinesn.test', 'renaud.nicolas@tontinesn.test'];
        $this->attachMembers($t, $members);

        // Cycle 1 : terminé (gbagné par rbousquet à 12%)
        $c1 = $this->makeCycle($t, 1, 'paid',
            $this->now->copy()->subMonths(3)->addDays(5),
            $this->user('rbousquet@tontinesn.test'),
            $this->now->copy()->subMonths(3));
        $this->payAll($c1, $members, 30000, $this->now->copy()->subMonths(3)->subDay(), 'card');
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('rbousquet@tontinesn.test')->id, 'bid_rate' => 12.00]);
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('npaul@tontinesn.test')->id, 'bid_rate' => 8.50]);
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('bleroy@tontinesn.test')->id, 'bid_rate' => 5.00]);

        // Cycle 2 : terminé (gagné par npaul à 15%)
        $c2 = $this->makeCycle($t, 2, 'paid',
            $this->now->copy()->subMonths(2)->addDays(5),
            $this->user('npaul@tontinesn.test'),
            $this->now->copy()->subMonths(2));
        $this->payAll($c2, $members, 30000, $this->now->copy()->subMonths(2)->subDay(), 'cash');
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('npaul@tontinesn.test')->id, 'bid_rate' => 15.00]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('gilles.guillaume@tontinesn.test')->id, 'bid_rate' => 10.00]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('rbousquet@tontinesn.test')->id, 'bid_rate' => 7.50]);

        // Cycle 3 : en cours avec enchères
        $c3 = $this->makeCycle($t, 3, 'pending',
            $this->now->copy()->subMonth()->addDays(5), null, null);
        $this->payAll($c3, $members, 30000, $this->now->copy()->subMonth()->subDay());
        AuctionBid::create(['cycle_id' => $c3->id, 'user_id' => $this->user('renaud.nicolas@tontinesn.test')->id, 'bid_rate' => 18.00]);
        AuctionBid::create(['cycle_id' => $c3->id, 'user_id' => $this->user('bleroy@tontinesn.test')->id, 'bid_rate' => 11.00]);
        AuctionBid::create(['cycle_id' => $c3->id, 'user_id' => $this->user('gilles.guillaume@tontinesn.test')->id, 'bid_rate' => 6.50]);
        AuctionBid::create(['cycle_id' => $c3->id, 'user_id' => $this->user('npaul@tontinesn.test')->id, 'bid_rate' => 4.00]);

        $this->command?->info('  ✓ 3 cycles avec enchères · 5 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 6 : Enchères Teranga — Auction
    //  2 cycles avec enchères, 4 membres, 20 000 F
    // ──────────────────────────────────────────────
    private function createEncheresTeranga(): void
    {
        $this->command?->info("\n--- 6. Enchères Teranga (auction, 20 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'ENC002'],
            [
                'name' => 'Tontine Enchères Teranga',
                'description' => 'Nouvelle formule enchères qui cartonne à Thiès — 20 000 FCFA/mois.',
                'amount' => 20000, 'frequency' => 'monthly', 'type' => 'auction',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(2),
                'max_members' => 4, 'quorum' => 1, 'penalty_rate' => 10,
                'draw_method' => 'random',
                'created_by' => $this->user('nbonnin@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['nbonnin@tontinesn.test', 'hugues@tontinesn.test',
            'leroux.alexandre@tontinesn.test', 'garnier.alphonse@tontinesn.test'];
        $this->attachMembers($t, $members);

        // Cycle 1 : terminé (gagné par nbonnin à 10%)
        $c1 = $this->makeCycle($t, 1, 'paid',
            $this->now->copy()->subMonths(2)->addDays(3),
            $this->user('nbonnin@tontinesn.test'),
            $this->now->copy()->subMonths(2));
        $this->payAll($c1, $members, 20000, $this->now->copy()->subMonths(2)->subDay(), 'free_money');
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('nbonnin@tontinesn.test')->id, 'bid_rate' => 10.00]);
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('hugues@tontinesn.test')->id, 'bid_rate' => 7.50]);

        // Cycle 2 : en cours
        $c2 = $this->makeCycle($t, 2, 'pending',
            $this->now->copy()->subMonth()->addDays(3), null, null);
        $this->payAll($c2, $members, 20000, $this->now->copy()->subMonth()->subDay());
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('leroux.alexandre@tontinesn.test')->id, 'bid_rate' => 14.00]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('garnier.alphonse@tontinesn.test')->id, 'bid_rate' => 9.00]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('hugues@tontinesn.test')->id, 'bid_rate' => 5.50]);

        $this->command?->info('  ✓ 2 cycles avec enchères · 4 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 7 : Épargne HLM — Forced Saving
    //  5 cycles, 6 membres, 40 000 F/mois
    // ──────────────────────────────────────────────
    private function createEpargneHlm(): void
    {
        $this->command?->info("\n--- 7. Épargne HLM (forced saving, 40 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'EPG001'],
            [
                'name' => 'Tontine Épargne HLM',
                'description' => 'Épargne forcée pour l\'achat groupé de fournitures scolaires — 40 000 F/mois.',
                'amount' => 40000, 'frequency' => 'monthly', 'type' => 'forced_saving',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(4),
                'max_members' => 6, 'quorum' => 1, 'penalty_rate' => 5,
                'draw_method' => 'sequential',
                'created_by' => $this->user('tessier@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['tessier@tontinesn.test', 'manager@tontinesn.test', 'fatou@tontinesn.test',
            'membre@tontinesn.test', 'lacombe.franck@tontinesn.test', 'diaz.salome@tontinesn.test'];
        $this->attachMembers($t, $members);

        for ($i = 1; $i <= 5; $i++) {
            $due = $this->now->copy()->subMonths(5 - $i);
            $isPartial = ($i === 5);
            $status = $isPartial ? 'partial' : 'paid';

            $c = $this->makeCycle($t, $i, $status, $due, null, null);

            foreach ($members as $email) {
                if ($isPartial && $email === 'membre@tontinesn.test') {
                    continue;
                }
                Transaction::create([
                    'cycle_id' => $c->id, 'user_id' => $this->user($email)->id,
                    'amount' => 40000, 'method' => 'orange_money', 'status' => 'success',
                    'paid_at' => $due->copy()->subDay(),
                    'type' => 'cycle_payment',
                ]);
            }
        }
        $this->command?->info('  ✓ 5 cycles (4 payés + 1 partiel) · 6 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 8 : Épargne Cité — Forced Saving, Private
    //  3 cycles, 4 membres, 35 000 F/mois
    // ──────────────────────────────────────────────
    private function createEpargneCite(): void
    {
        $this->command?->info("\n--- 8. Épargne Cité (forced saving, privé, 35 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'EPG002'],
            [
                'name' => 'Tontine Épargne Cité',
                'description' => 'Épargne forcée privée — 4 amies pour les sorties et vacances.',
                'amount' => 35000, 'frequency' => 'monthly', 'type' => 'forced_saving',
                'status' => 'active', 'visibility' => 'private',
                'start_date' => $this->now->copy()->subMonths(3),
                'max_members' => 4, 'quorum' => 1, 'penalty_rate' => 0,
                'draw_method' => 'sequential',
                'created_by' => $this->user('manager@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['manager@tontinesn.test', 'jlemonnier@tontinesn.test',
            'leclerc.sabine@tontinesn.test', 'fournier.amedee@tontinesn.test'];
        $this->attachMembers($t, $members);

        for ($i = 1; $i <= 3; $i++) {
            $due = $this->now->copy()->subMonths(3 - $i);
            $c = $this->makeCycle($t, $i, 'paid', $due, null, null);
            $methods = ['wave', 'wave', 'card'];
            $this->payAll($c, $members, 35000, $due->copy()->subDay(), $methods[$i - 1]);
        }
        $this->command?->info('  ✓ 3 cycles payés · 4 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 9 : Cérémonial Mariage Aminata
    //  1 cycle payé + 1 en cours, 5 membres, 20 000
    // ──────────────────────────────────────────────
    private function createCeremonialMariage(): void
    {
        $this->command?->info("\n--- 9. Cérémonial Mariage Aminata (cérémonial, 20 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'CER001'],
            [
                'name' => 'Cagnotte Mariage Aminata',
                'description' => 'Cagnotte cérémoniale pour le mariage d\'Aminata — 20 000 FCFA/mois.',
                'amount' => 20000, 'frequency' => 'monthly', 'type' => 'ceremonial',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(2),
                'max_members' => 5, 'quorum' => 1, 'penalty_rate' => 0,
                'draw_method' => 'sequential',
                'created_by' => $this->user('membre@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['membre@tontinesn.test', 'fatou@tontinesn.test', 'admin@tontinesn.test',
            'tessier@tontinesn.test', 'maryse@tontinesn.test'];
        $this->attachMembers($t, $members);

        // Cycle 1 : payé
        $c1 = $this->makeCycle($t, 1, 'paid',
            $this->now->copy()->subMonths(2), $this->user('membre@tontinesn.test'),
            $this->now->copy()->subMonths(2)->addDays(2));
        $this->payAll($c1, $members, 20000, $this->now->copy()->subMonths(2)->subDay());

        // Cycle 2 : en attente
        $c2 = $this->makeCycle($t, 2, 'pending',
            $this->now->copy()->subMonth(), null, null);

        // 1 cash en attente de confirmation admin (pour montrer le bouton Confirmer)
        Transaction::create([
            'cycle_id' => $c2->id, 'user_id' => $this->user('fatou@tontinesn.test')->id,
            'amount' => 20000, 'method' => 'cash', 'status' => 'pending',
            'paid_at' => null, 'type' => 'cycle_payment',
        ]);

        $this->command?->info('  ✓ 2 cycles (1 payé + 1 en attente) · 5 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 10 : Castors Tirage — Fixed, Random, Veto
    //  1 cycle partiel avec veto, 6 membres, 15 000 F
    // ──────────────────────────────────────────────
    private function createCastorsTirage(): void
    {
        $this->command?->info("\n--- 10. Castors Tirage (aléatoire + véto, 15 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'CAS001'],
            [
                'name' => 'Tontine Castors Tirage',
                'description' => 'Tontine avec tirage pondéré par le score et droit de véto démocratique.',
                'amount' => 15000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subWeeks(3),
                'max_members' => 6, 'quorum' => 60, 'penalty_rate' => 10,
                'draw_method' => 'random', 'weighted_draw' => true, 'veto_threshold' => 33,
                'created_by' => $this->user('hugues@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['membre@tontinesn.test', 'tessier@tontinesn.test', 'nbonnin@tontinesn.test',
            'hugues@tontinesn.test', 'menard.chantal@tontinesn.test', 'guerin.marc@tontinesn.test'];
        $this->attachMembers($t, $members);

        // Cycle 1 : tirage contesté — 2 vetos
        $c1 = $this->makeCycle($t, 1, 'partial',
            $this->now->copy()->subDays(5), $this->user('nbonnin@tontinesn.test'),
            $this->now->copy()->subDays(10));
        // 4/6 ont payé
        foreach (['membre@tontinesn.test', 'tessier@tontinesn.test', 'hugues@tontinesn.test', 'guerin.marc@tontinesn.test'] as $email) {
            Transaction::create([
                'cycle_id' => $c1->id, 'user_id' => $this->user($email)->id,
                'amount' => 15000, 'method' => 'orange_money', 'status' => 'success',
                'paid_at' => $this->now->copy()->subDays(6),
                'type' => 'cycle_payment',
            ]);
        }
        CycleVeto::create(['cycle_id' => $c1->id, 'user_id' => $this->user('nbonnin@tontinesn.test')->id]);
        CycleVeto::create(['cycle_id' => $c1->id, 'user_id' => $this->user('menard.chantal@tontinesn.test')->id]);

        $this->makeCycle($t, 2, 'pending', $this->now->copy()->addDays(25), null, null);
        $this->command?->info('  ✓ 1 cycle partiel + 2 vetos · 6 membres');
    }

    // ──────────────────────────────────────────────
    //  Tontine 11 : Suspendue — Fixed, Monthly
    //  0 cycles, 3 membres, suspended, montant 10 000
    // ──────────────────────────────────────────────
    private function createTontineSuspendue(): void
    {
        $this->command?->info("\n--- 11. Tontine Suspendue (suspendue, 10 000 F) ---");
        $t = Tontine::firstOrCreate(
            ['code' => 'SUS001'],
            [
                'name' => 'Tontine Suspendue',
                'description' => 'Tontine suspendue pour non-respect des règles — exemple de statut suspended.',
                'amount' => 10000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'suspended', 'visibility' => 'private',
                'start_date' => $this->now->copy()->subMonth(),
                'max_members' => 3, 'quorum' => 1,
                'draw_method' => 'sequential',
                'created_by' => $this->user('admin@tontinesn.test')->id,
            ]
        );
        if (! $t->wasRecentlyCreated) {
            $this->command?->info('  ✓ existe déjà');

            return;
        }

        $members = ['admin@tontinesn.test', 'fatou@tontinesn.test', 'membre@tontinesn.test'];
        $this->attachMembers($t, $members);
        $this->command?->info('  ✓ suspendue · 3 membres');
    }

    // ──────────────────────────────────────────────
    //  Transactions P2P (QR)
    // ──────────────────────────────────────────────
    private function createKycDocuments(): void
    {
        $this->command?->info("\n--- Documents KYC démo ---");
        $path = 'kyc/demo-kyc.png';

        if (! Storage::disk('local')->exists($path)) {
            Storage::disk('local')->makeDirectory('kyc');
            $img = imagecreate(600, 400);
            $bg = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            $red = imagecolorallocate($img, 239, 51, 64);
            $gray = imagecolorallocate($img, 100, 100, 100);
            imagerectangle($img, 10, 10, 589, 389, $black);
            imagestring($img, 5, 40, 30, 'PIECE D\'IDENTITE', $red);
            imagestring($img, 4, 40, 70, 'Carte nationale d\'identite', $black);
            imagestring($img, 3, 40, 110, 'N: SN-'.date('Y').'-'.rand(100000, 999999), $black);
            imagestring($img, 3, 40, 140, 'Delivree le: '.now()->subYears(2)->format('d/m/Y'), $black);
            imageline($img, 40, 180, 560, 180, $gray);
            imagestring($img, 5, 40, 210, 'NOM DU TITULAIRE', $black);
            imagestring($img, 3, 40, 250, 'Ne(e) le: 01/01/1990', $black);
            imagestring($img, 3, 40, 280, 'Nationalite: SenegalaiSe', $black);
            imagepng($img, Storage::disk('local')->path($path));
            imagedestroy($img);
            $this->command?->info('  ✓ Image KYC generee');
        }

        $now = now();
        $nouveaux = [
            ['aminata.fall@tontinesn.test', 'Aminata Fall', '+221 77 777 77 01'],
            ['ousmane.diop@tontinesn.test', 'Ousmane Diop', '+221 76 666 66 02'],
        ];
        $hash = hash_file('sha256', Storage::disk('local')->path($path));
        $count = 0;
        foreach ($nouveaux as [$email, $name, $phone]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone_number' => $phone,
                    'password' => bcrypt('Membre2024!'),
                    'role' => 'member',
                    'is_active' => true,
                    'kyc_document' => $path,
                    'kyc_verified' => false,
                    'kyc_status' => 'pending',
                    'kyc_document_hash' => $hash,
                    'email_verified_at' => $now,
                    'onboarding_completed' => true,
                    'created_at' => $now->copy()->subMonth(),
                ]
            );
            $count++;
        }
        $this->command?->info("  ✓ $count utilisateurs avec KYC en attente");
    }

    private function createP2PTransactions(): void
    {
        $this->command?->info("\n--- Transactions QR P2P ---");
        $anyCycleId = Cycle::inRandomOrder()->value('id');
        if (! $anyCycleId) {
            $this->command?->info('  ⚠ Aucun cycle trouvé');

            return;
        }

        $p2ps = [
            ['from' => 'membre@tontinesn.test',  'amount' => 15000, 'desc' => 'Remboursement course',
                'method' => 'direct_transfer', 'days_ago' => 5, 'meta' => ['receiver' => 'admin@tontinesn.test']],
            ['from' => 'jlemonnier@tontinesn.test', 'amount' => 25000, 'desc' => 'Cotisation repas famille',
                'method' => 'direct_transfer', 'days_ago' => 3, 'meta' => ['receiver' => 'pierre@tontinesn.test']],
            ['from' => 'hugues@tontinesn.test', 'amount' => 10000, 'desc' => 'Cadeau anniversaire',
                'method' => 'free_money', 'days_ago' => 1, 'meta' => ['receiver' => 'maryse@tontinesn.test']],
            ['from' => 'fatou@tontinesn.test', 'amount' => 50000, 'desc' => 'Contribution cérémonie',
                'method' => 'direct_transfer', 'days_ago' => 0, 'meta' => ['receiver' => 'membre@tontinesn.test']],
            ['from' => 'tessier@tontinesn.test', 'amount' => 7500, 'desc' => 'Part taxi groupe',
                'method' => 'direct_transfer', 'days_ago' => 7, 'meta' => ['receiver' => 'rbousquet@tontinesn.test']],
            ['from' => 'manager@tontinesn.test', 'amount' => 20000, 'desc' => 'Prêt remboursé',
                'method' => 'direct_transfer', 'days_ago' => 2, 'meta' => ['receiver' => 'tessier@tontinesn.test']],
        ];
        $count = 0;
        foreach ($p2ps as $p2p) {
            $user = $this->user($p2p['from']);
            if (! $user) {
                continue;
            }
            $exists = Transaction::where('type', 'qr_p2p')
                ->where('user_id', $user->id)
                ->where('amount', $p2p['amount'])
                ->exists();
            if (! $exists) {
                Transaction::create([
                    'cycle_id' => $anyCycleId, 'user_id' => $user->id,
                    'amount' => $p2p['amount'], 'method' => $p2p['method'],
                    'type' => 'qr_p2p', 'description' => 'Paiement QR P2P — '.$p2p['desc'],
                    'metadata' => json_encode($p2p['meta']),
                    'status' => 'success', 'paid_at' => $this->now->copy()->subDays($p2p['days_ago']),
                ]);
                $count++;
            }
        }
        $this->command?->info("  ✓ $count transactions QR P2P créées");
    }

    // ──────────────────────────────────────────────
    //  Messages Chat
    // ──────────────────────────────────────────────
    private function createChatMessages(): void
    {
        $this->command?->info("\n--- Messages Chat ---");
        $tontines = Tontine::whereIn('code', ['FAM001', 'THI001'])->get();
        foreach ($tontines as $tontine) {
            $members = $tontine->activeMembers->pluck('id')->toArray();
            if (count($members) < 3) {
                continue;
            }

            $msgs = match ($tontine->code) {
                'FAM001' => [
                    ['Salam aleykoum ! Le cycle 6 commence, qui a payé ? 🙏', 0],
                    ['Moi c\'est bon, Wave envoyé ✅', 1],
                    ['Je viens de payer aussi 💰', 2],
                    ['Quand est-ce que Pierre va payer ? Il est toujours en retard', 5],
                    ['Je lui ai envoyé un rappel, il a dit demain 📲', 0],
                    ['Parfait, merci admin ! 👍', 1],
                ],
                'THI001' => [
                    ['Yo les gars, on fait la collecte chez le même point ?', 0],
                    ['Oui, chez Moussa à côté du marché 🏪', 1],
                    ['Je passe à 17h après le boulot 🕐', 2],
                    ['Semaine prochaine c\'est le tour de Amadou 👏', 0],
                    ['Excellente nouvelle ! Je prépare le thé 🍵', 3],
                    ['Je ramène le pain 🥖', 4],
                ],
                default => [],
            };

            $count = 0;
            foreach ($msgs as $i => [$msg, $mi]) {
                $exists = ChatMessage::where('tontine_id', $tontine->id)
                    ->where('message', $msg)->exists();
                if (! $exists) {
                    ChatMessage::create([
                        'tontine_id' => $tontine->id, 'user_id' => $members[$mi] ?? $members[0],
                        'message' => $msg, 'created_at' => $this->now->copy()->subDays(14 - $i * 2),
                    ]);
                    $count++;
                }
            }
            $this->command?->info("  ✓ $count messages dans {$tontine->name}");
        }
    }

    // ──────────────────────────────────────────────
    //  Badges & Scores
    // ──────────────────────────────────────────────
    private function awardBadgesAndScores(): void
    {
        $this->command?->info("\n--- Badges & Scores ---");

        // Définir des streaks réalistes selon l'historique des paiements
        User::whereIn('email', [
            'admin@tontinesn.test', 'fatou@tontinesn.test', 'tessier@tontinesn.test',
        ])->update(['payment_streak' => 10, 'max_streak' => 12]);

        User::whereIn('email', [
            'membre@tontinesn.test', 'nbonnin@tontinesn.test', 'hugues@tontinesn.test',
            'pierre@tontinesn.test', 'manager@tontinesn.test',
        ])->update(['payment_streak' => 6, 'max_streak' => 8]);

        User::whereIn('email', [
            'rbousquet@tontinesn.test', 'jlemonnier@tontinesn.test', 'ilaurent@tontinesn.test',
            'npaul@tontinesn.test', 'bleroy@tontinesn.test',
        ])->update(['payment_streak' => 4, 'max_streak' => 5]);

        User::whereIn('email', [
            'hcarre@tontinesn.test', 'roland@tontinesn.test', 'juliette@tontinesn.test',
            'maurice@tontinesn.test', 'lacombe.franck@tontinesn.test', 'diaz.salome@tontinesn.test',
            'gilles.guillaume@tontinesn.test', 'renaud.nicolas@tontinesn.test',
        ])->update(['payment_streak' => 2, 'max_streak' => 3]);

        $gamification = app(GamificationService::class);
        $scoring = app(CreditScoringService::class);

        foreach ($this->users as $email => $user) {
            $badges = $gamification->checkAndAwardBadges($user);
            if ($badges->isNotEmpty()) {
                $this->command?->info("  🏅 $email : ".$badges->pluck('slug')->implode(', '));
            }
        }

        $count = 0;
        foreach ($this->users as $user) {
            $scoring->calculate($user);
            $count++;
        }
        $this->command?->info("  ✓ Scores recalculés pour $count utilisateurs");
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────
    private function attachMembers(Tontine $tontine, array $emails): void
    {
        $data = [];
        foreach ($emails as $i => $email) {
            $user = $this->user($email);
            if (! $user) {
                $this->command?->warn("  ⚠ Utilisateur introuvable : $email");

                continue;
            }
            $data[$user->id] = [
                'status' => 'active', 'position' => $i + 1,
                'joined_at' => $tontine->start_date->copy()->addDays($i),
                'start_cycle_number' => 1,
            ];
        }
        $tontine->members()->syncWithoutDetaching($data);
    }

    private function makeCycle(Tontine $t, int $num, string $status, Carbon $dueDate,
        ?User $benef = null, ?Carbon $drawnAt = null): Cycle
    {
        return Cycle::create([
            'tontine_id' => $t->id, 'cycle_number' => $num,
            'beneficiary_id' => $benef?->id, 'due_date' => $dueDate,
            'status' => $status, 'total_collected' => 0,
            'draw_hash' => $drawnAt ? hash('sha256', $t->code."-$num-{$benef?->id}-".$drawnAt->timestamp) : null,
            'drawn_at' => $drawnAt,
        ]);
    }

    private function payAll(Cycle $cycle, array $emails, int $amount, Carbon $paidAt, string $method = 'wave'): void
    {
        foreach ($emails as $email) {
            $user = $this->user($email);
            if (! $user) {
                continue;
            }
            Transaction::create([
                'cycle_id' => $cycle->id, 'user_id' => $user->id,
                'amount' => $amount, 'method' => $method, 'status' => 'success',
                'paid_at' => $paidAt, 'type' => 'cycle_payment',
            ]);
        }
    }
}

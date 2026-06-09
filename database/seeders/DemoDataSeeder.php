<?php
declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{
    AuctionBid, ChatMessage, Cycle, CycleVeto, Tontine, Transaction, User
};
use App\Services\CreditScoringService;
use App\Services\GamificationService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    private Carbon $now;
    private array $users = [];

    public function run(): void
    {
        $this->now = now();
        $this->command?->info('=== DONNÉES DÉMO STRATÉGIQUES ===');

        $this->loadUsers();
        $this->createTontineSandaga();
        $this->createTontineOuakam();
        $this->createTontineMedina();
        $this->createTontineDakar();
        $this->createTontineAuctionLiberte();
        $this->createTontineAuctionTeranga();
        $this->createTontineForcedHlm();
        $this->createTontineForcedCite();
        $this->createTontineCeremonial();
        $this->createTontineVeto();
        $this->createP2PTransactions();
        $this->createChatMessages();
        $this->awardBadgesAndScores();

        $this->command?->info('✓ TERMINÉ — Toutes les données démo sont prêtes.');
    }

    private function loadUsers(): void
    {
        // Indexer les utilisateurs par email pour un lookup facile
        $emails = [
            'admin@tontinesn.test',
            'fatou@tontinesn.test',
            'membre@tontinesn.test',
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
        ];
        foreach ($emails as $email) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $this->users[$email] = $user;
            }
        }
        $this->command?->info('  ✓ ' . count($this->users) . ' utilisateurs chargés');
    }

    private function user(string $email): User
    {
        return $this->users[$email];
    }

    private function createTontineSandaga(): void
    {
        $this->command?->info("\n--- Tontine Sandaga (fixe) ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'SAN001'],
            [
                'name' => 'Tontine Sandaga',
                'description' => 'Tontine mensuelle du marché Sandaga — 8 commerçantes pour 250 000 FCFA par tour.',
                'amount' => 25000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(3),
                'max_members' => 8, 'quorum' => 60, 'penalty_rate' => 10,
                'draw_method' => 'sequential', 'created_by' => $this->user('fatou@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['fatou@tontinesn.test', 'membre@tontinesn.test', 'tessier@tontinesn.test',
                     'rbousquet@tontinesn.test', 'nbonnin@tontinesn.test', 'jlemonnier@tontinesn.test',
                     'hugues@tontinesn.test', 'pierre@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        // Cycle 1 — payé (il y a 2 mois)
        $c1 = $this->makeCycle($tontine, 1, 'paid', $this->now->copy()->subMonths(2)->addDays(5),
            $this->user('fatou@tontinesn.test'), $this->now->copy()->subMonths(2));
        $this->payAll($c1, $members, 25000, $this->now->copy()->subMonths(2)->subDays(2));

        // Cycle 2 — payé (il y a 1 mois)
        $benef2 = $this->user('membre@tontinesn.test');
        $c2 = $this->makeCycle($tontine, 2, 'paid', $this->now->copy()->subMonth()->addDays(5),
            $benef2, $this->now->copy()->subMonth());
        $this->payAll($c2, $members, 25000, $this->now->copy()->subMonth()->subDays(1));

        // Cycle 3 — en cours (partiel, 1 membre en retard)
        $benef3 = $this->user('tessier@tontinesn.test');
        $c3 = $this->makeCycle($tontine, 3, 'partial', $this->now->copy()->addDays(5),
            $benef3, $this->now->copy()->subDays(2));
        $earlyPayers = ['fatou@tontinesn.test', 'membre@tontinesn.test', 'rbousquet@tontinesn.test',
                         'nbonnin@tontinesn.test', 'jlemonnier@tontinesn.test', 'hugues@tontinesn.test',
                         'pierre@tontinesn.test'];
        foreach ($earlyPayers as $email) {
            Transaction::create(['cycle_id' => $c3->id, 'user_id' => $this->user($email)->id,
                'amount' => 25000, 'method' => 'wave', 'status' => 'success',
                'paid_at' => $this->now->copy()->subDays(1), 'type' => 'cycle_payment']);
        }

        // Cycle 4 — prévu
        $this->makeCycle($tontine, 4, 'pending', $this->now->copy()->addDays(35), null, null);

        $this->command?->info('  ✓ Créée : 3 cycles (2 payés + 1 partiel) · 8 membres');
    }

    private function createTontineOuakam(): void
    {
        $this->command?->info("\n--- Tontine Ouakam (fixe) ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'OUA001'],
            [
                'name' => 'Tontine Ouakam',
                'description' => 'Tontine hebdomadaire du quartier Ouakam — pour les petites dépenses quotidiennes.',
                'amount' => 5000, 'frequency' => 'weekly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subWeeks(6),
                'max_members' => 6, 'quorum' => 50, 'penalty_rate' => 5,
                'draw_method' => 'sequential', 'created_by' => $this->user('ilaurent@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['ilaurent@tontinesn.test', 'hcarre@tontinesn.test', 'roland@tontinesn.test',
                     'npaul@tontinesn.test', 'bleroy@tontinesn.test', 'juliette@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        // 5 cycles hebdomadaires payés
        for ($i = 1; $i <= 5; $i++) {
            $benef = $this->user($members[$i - 1]);
            $c = $this->makeCycle($tontine, $i, 'paid',
                $this->now->copy()->subWeeks(6 - $i)->addDays(2),
                $benef, $this->now->copy()->subWeeks(6 - $i));
            $this->payAll($c, $members, 5000, $this->now->copy()->subWeeks(6 - $i)->subDay());
        }

        // Cycle 6 — en cours (retard, overdue)
        $c6 = $this->makeCycle($tontine, 6, 'overdue', $this->now->copy()->subDays(2), null, null);
        // 4/6 ont payé
        $latePayers = array_slice($members, 0, 4);
        foreach ($latePayers as $email) {
            Transaction::create(['cycle_id' => $c6->id, 'user_id' => $this->user($email)->id,
                'amount' => 5000, 'method' => 'cash', 'status' => 'success',
                'paid_at' => $this->now->copy()->subDays(3), 'type' => 'cycle_payment']);
        }

        $this->command?->info('  ✓ Créée : 6 cycles (5 payés + 1 overdue) · 6 membres');
    }

    private function createTontineMedina(): void
    {
        $this->command?->info("\n--- Tontine Médina (nouvelle) ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'MED001'],
            [
                'name' => 'Tontine Médina',
                'description' => 'Nouvelle tontine du quartier Médina — inscriptions en cours, démarrage dans 2 semaines.',
                'amount' => 15000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'pending', 'visibility' => 'public',
                'start_date' => $this->now->copy()->addWeeks(2),
                'max_members' => 10, 'quorum' => 50, 'penalty_rate' => 10,
                'draw_method' => 'random', 'weighted_draw' => true,
                'created_by' => $this->user('maurice@tontinesn.test')->id,
            ]
        );
        if ($tontine->wasRecentlyCreated) {
            $this->attachMembers($tontine, ['maurice@tontinesn.test']);
            $this->command?->info('  ✓ Créée : en attente de membres');
        }
    }

    private function createTontineDakar(): void
    {
        $this->command?->info("\n--- Tontine Dakar (terminée) ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'DAK001'],
            [
                'name' => 'Tontine Dakar Plateau',
                'description' => 'Tontine de 6 mois terminée avec succès — tous les membres ont reçu leur tour.',
                'amount' => 50000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'completed', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(6),
                'end_date' => $this->now->copy()->subMonth(),
                'max_members' => 6, 'quorum' => 80, 'penalty_rate' => 15,
                'draw_method' => 'sequential',
                'created_by' => $this->user('admin@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['admin@tontinesn.test', 'fatou@tontinesn.test', 'membre@tontinesn.test',
                     'tessier@tontinesn.test', 'rbousquet@tontinesn.test', 'nbonnin@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        for ($i = 1; $i <= 6; $i++) {
            $benef = $this->user($members[$i - 1]);
            $c = $this->makeCycle($tontine, $i, 'paid',
                $this->now->copy()->subMonths(6 - $i)->addDays(3),
                $benef, $this->now->copy()->subMonths(6 - $i));
            // Paiements (certains en retard pour montrer les pénalités)
            foreach ($members as $email) {
                $isLate = ($email === 'rbousquet@tontinesn.test' && $i === 3) ||
                          ($email === 'membre@tontinesn.test' && $i === 5);
                Transaction::create(['cycle_id' => $c->id, 'user_id' => $this->user($email)->id,
                    'amount' => 50000, 'method' => 'wave', 'status' => 'success',
                    'paid_at' => $isLate ? $c->due_date->copy()->addDays(3) : $c->due_date->copy()->subDay(),
                    'type' => 'cycle_payment']);
            }
        }
        $this->command?->info('  ✓ Créée : 6 cycles terminés · 6 membres');
    }

    private function createTontineAuctionLiberte(): void
    {
        $this->command?->info("\n--- Tontine Enchères Liberté ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'ENC001'],
            [
                'name' => 'Tontine Enchères Liberté',
                'description' => 'Tontine aux enchères du quartier Liberté — le plus offrant remporte le pot.',
                'amount' => 30000, 'frequency' => 'monthly', 'type' => 'auction',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subWeeks(2),
                'max_members' => 5, 'quorum' => 1, 'penalty_rate' => 10,
                'draw_method' => 'sequential',
                'created_by' => $this->user('rbousquet@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['rbousquet@tontinesn.test', 'npaul@tontinesn.test', 'bleroy@tontinesn.test',
                     'juliette@tontinesn.test', 'maurice@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        $c1 = $this->makeCycle($tontine, 1, 'pending', $this->now->copy()->addDays(14), null, null);
        $bids = [15.00, 8.50, 12.00, 5.00, 2.00];
        foreach ($members as $i => $email) {
            AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user($email)->id, 'bid_rate' => $bids[$i]]);
        }
        $this->command?->info('  ✓ Créée : 1 cycle avec 5 offres enchères');
    }

    private function createTontineAuctionTeranga(): void
    {
        $this->command?->info("\n--- Tontine Enchères Teranga ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'ENC002'],
            [
                'name' => 'Tontine Enchères Teranga',
                'description' => 'Tontine aux enchères — une nouvelle formule qui cartonne à Thiès.',
                'amount' => 50000, 'frequency' => 'monthly', 'type' => 'auction',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(2),
                'max_members' => 4, 'quorum' => 1, 'penalty_rate' => 10,
                'draw_method' => 'sequential',
                'created_by' => $this->user('nbonnin@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['nbonnin@tontinesn.test', 'jlemonnier@tontinesn.test', 'hugues@tontinesn.test', 'pierre@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        // Cycle 1 — terminé avec enchères
        $c1 = $this->makeCycle($tontine, 1, 'paid', $this->now->copy()->subWeeks(4)->addDays(3),
            $this->user('nbonnin@tontinesn.test'), $this->now->copy()->subWeeks(4));
        foreach ($members as $email) {
            Transaction::create(['cycle_id' => $c1->id, 'user_id' => $this->user($email)->id,
                'amount' => 50000, 'method' => 'orange_money', 'status' => 'success',
                'paid_at' => $this->now->copy()->subWeeks(4)->subDay(), 'type' => 'cycle_payment']);
        }
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('nbonnin@tontinesn.test')->id, 'bid_rate' => 10.00]);
        AuctionBid::create(['cycle_id' => $c1->id, 'user_id' => $this->user('jlemonnier@tontinesn.test')->id, 'bid_rate' => 7.50]);

        // Cycle 2 — en cours avec enchères
        $c2 = $this->makeCycle($tontine, 2, 'pending', $this->now->copy()->addDays(10), null, null);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('jlemonnier@tontinesn.test')->id, 'bid_rate' => 12.50]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('pierre@tontinesn.test')->id, 'bid_rate' => 8.00]);
        AuctionBid::create(['cycle_id' => $c2->id, 'user_id' => $this->user('hugues@tontinesn.test')->id, 'bid_rate' => 3.50]);

        $this->command?->info('  ✓ Créée : 2 cycles avec enchères');
    }

    private function createTontineForcedHlm(): void
    {
        $this->command?->info("\n--- Tontine Épargne HLM ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'EPG001'],
            [
                'name' => 'Tontine Épargne HLM',
                'description' => 'Épargne forcée pour l\'achat groupé de fournitures scolaires.',
                'amount' => 20000, 'frequency' => 'monthly', 'type' => 'forced_saving',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonths(2),
                'max_members' => 4, 'quorum' => 1, 'penalty_rate' => 5,
                'draw_method' => 'sequential',
                'created_by' => $this->user('tessier@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['tessier@tontinesn.test', 'ilaurent@tontinesn.test', 'hcarre@tontinesn.test', 'roland@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        // Cycle 1 — payé
        $c1 = $this->makeCycle($tontine, 1, 'paid', $this->now->copy()->subWeeks(5),
            $this->user('tessier@tontinesn.test'), $this->now->copy()->subWeeks(5));
        $this->payAll($c1, $members, 20000, $this->now->copy()->subWeeks(5)->subDay());

        // Cycle 2 — payé
        $c2 = $this->makeCycle($tontine, 2, 'paid', $this->now->copy()->subWeek(),
            $this->user('ilaurent@tontinesn.test'), $this->now->copy()->subWeek());
        $this->payAll($c2, $members, 20000, $this->now->copy()->subWeek()->subDay());

        // Cycle 3 — partiel
        $c3 = $this->makeCycle($tontine, 3, 'partial', $this->now->copy()->addDays(23), null, null);
        foreach (['tessier@tontinesn.test', 'roland@tontinesn.test'] as $email) {
            Transaction::create(['cycle_id' => $c3->id, 'user_id' => $this->user($email)->id,
                'amount' => 20000, 'method' => 'orange_money', 'status' => 'success',
                'paid_at' => $this->now->copy()->subDays(2), 'type' => 'cycle_payment']);
        }

        $this->command?->info('  ✓ Créée : 3 cycles (2 payés + 1 partiel)');
    }

    private function createTontineForcedCite(): void
    {
        $this->command?->info("\n--- Tontine Épargne Cité ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'EPG002'],
            [
                'name' => 'Tontine Épargne Cité',
                'description' => 'Épargne forcée pour les sorties familiales — 3 membres, 10 000 F/semaine.',
                'amount' => 10000, 'frequency' => 'weekly', 'type' => 'forced_saving',
                'status' => 'active', 'visibility' => 'private',
                'start_date' => $this->now->copy()->subWeeks(3),
                'max_members' => 3, 'quorum' => 1, 'penalty_rate' => 0,
                'draw_method' => 'sequential',
                'created_by' => $this->user('npaul@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['npaul@tontinesn.test', 'bleroy@tontinesn.test', 'juliette@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        for ($i = 1; $i <= 3; $i++) {
            $c = $this->makeCycle($tontine, $i, 'paid', $this->now->copy()->subWeeks(3 - $i),
                $this->user($members[$i - 1]), $this->now->copy()->subWeeks(3 - $i));
            $this->payAll($c, $members, 10000, $this->now->copy()->subWeeks(3 - $i)->subDay());
        }
        $this->command?->info('  ✓ Créée : 3 cycles payés · 3 membres');
    }

    private function createTontineCeremonial(): void
    {
        $this->command?->info("\n--- Tontine Cérémonial ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'CER001'],
            [
                'name' => 'Tontine Cérémonial Sandaga',
                'description' => 'Cagnotte cérémoniale pour les grands événements (baptêmes, mariages).',
                'amount' => 100000, 'frequency' => 'monthly', 'type' => 'ceremonial',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonth(),
                'max_members' => 4, 'quorum' => 1, 'penalty_rate' => 0,
                'draw_method' => 'sequential',
                'created_by' => $this->user('membre@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['membre@tontinesn.test', 'tessier@tontinesn.test', 'jlemonnier@tontinesn.test', 'hugues@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        $c1 = $this->makeCycle($tontine, 1, 'paid', $this->now->copy()->subWeeks(2),
            $this->user('membre@tontinesn.test'), $this->now->copy()->subWeeks(2));
        $this->payAll($c1, $members, 100000, $this->now->copy()->subWeeks(2)->subDay());

        $this->makeCycle($tontine, 2, 'pending', $this->now->copy()->addDays(14), null, null);
        $this->command?->info('  ✓ Créée : 1 cycle payé + 1 en attente');
    }

    private function createTontineVeto(): void
    {
        $this->command?->info("\n--- Tontine Castors Tirage (veto) ---");
        $tontine = Tontine::firstOrCreate(
            ['code' => 'CAS001'],
            [
                'name' => 'Tontine Castors Tirage',
                'description' => 'Tontine avec tirage pondéré par le score crédit — les membres peuvent exercer un droit de véto.',
                'amount' => 50000, 'frequency' => 'monthly', 'type' => 'fixed',
                'status' => 'active', 'visibility' => 'public',
                'start_date' => $this->now->copy()->subMonth(),
                'max_members' => 6, 'quorum' => 60, 'penalty_rate' => 10,
                'draw_method' => 'random', 'weighted_draw' => true, 'veto_threshold' => 33,
                'created_by' => $this->user('membre@tontinesn.test')->id,
            ]
        );
        if (!$tontine->wasRecentlyCreated) { $this->command?->info('  ✓ existe déjà'); return; }

        $members = ['membre@tontinesn.test', 'tessier@tontinesn.test', 'nbonnin@tontinesn.test',
                     'hugues@tontinesn.test', 'pierre@tontinesn.test', 'jlemonnier@tontinesn.test'];
        $this->attachMembers($tontine, $members);

        $c1 = $this->makeCycle($tontine, 1, 'partial', $this->now->copy()->addDays(10),
            $this->user('pierre@tontinesn.test'), $this->now->copy()->subDays(2));
        // 4/6 ont payé
        foreach (array_slice($members, 0, 4) as $email) {
            Transaction::create(['cycle_id' => $c1->id, 'user_id' => $this->user($email)->id,
                'amount' => 50000, 'method' => 'orange_money', 'status' => 'success',
                'paid_at' => $this->now->copy()->subDays(3), 'type' => 'cycle_payment']);
        }
        // 2 vetos
        foreach (['nbonnin@tontinesn.test', 'jlemonnier@tontinesn.test'] as $email) {
            CycleVeto::create(['cycle_id' => $c1->id, 'user_id' => $this->user($email)->id]);
        }

        $this->makeCycle($tontine, 2, 'pending', $this->now->copy()->addDays(40), null, null);
        $this->command?->info('  ✓ Créée : 1 cycle partiel avec 2 vetos');
    }

    private function createP2PTransactions(): void
    {
        $this->command?->info("\n--- Transactions QR P2P ---");
        $anyCycleId = Cycle::inRandomOrder()->value('id');
        if (!$anyCycleId) { $this->command?->info('  ⚠ Aucun cycle trouvé, P2P ignoré'); return; }

        $p2ps = [
            ['from' => 'membre@tontinesn.test', 'amount' => 15000, 'desc' => 'Remboursement course',
             'method' => 'direct_transfer', 'days_ago' => 3, 'meta' => ['receiver' => 'admin@tontinesn.test']],
            ['from' => 'jlemonnier@tontinesn.test', 'amount' => 25000, 'desc' => 'Cotisation repas famille',
             'method' => 'direct_transfer', 'days_ago' => 1, 'meta' => ['receiver' => 'pierre@tontinesn.test']],
            ['from' => 'hugues@tontinesn.test', 'amount' => 10000, 'desc' => 'Cadeau anniversaire',
             'method' => 'free_money', 'days_ago' => 0, 'meta' => ['receiver' => 'maryse@tontinesn.test']],
            ['from' => 'fatou@tontinesn.test', 'amount' => 50000, 'desc' => 'Contribution cérémonie',
             'method' => 'direct_transfer', 'days_ago' => 0, 'meta' => ['receiver' => 'membre@tontinesn.test']],
        ];
        $count = 0;
        foreach ($p2ps as $p2p) {
            $user = $this->user($p2p['from']);
            $exists = Transaction::where('type', 'qr_p2p')->where('user_id', $user->id)->where('amount', $p2p['amount'])->exists();
            if (!$exists) {
                Transaction::create([
                    'cycle_id' => $anyCycleId, 'user_id' => $user->id,
                    'amount' => $p2p['amount'], 'method' => $p2p['method'],
                    'type' => 'qr_p2p', 'description' => 'Paiement QR P2P — ' . $p2p['desc'],
                    'metadata' => json_encode($p2p['meta']),
                    'status' => 'success', 'paid_at' => $this->now->copy()->subDays($p2p['days_ago']),
                ]);
                $count++;
            }
        }
        $this->command?->info("  ✓ $count transactions QR P2P créées");
    }

    private function createChatMessages(): void
    {
        $this->command?->info("\n--- Messages Chat ---");
        $tontines = Tontine::whereIn('code', ['SAN001', 'OUA001'])->get();
        foreach ($tontines as $tontine) {
            $members = $tontine->activeMembers->pluck('id')->toArray();
            if (count($members) < 3) continue;
            $msgs = [
                ['Bonjour à tous ! Prêt pour le nouveau cycle ? 💪', 0],
                ['Présent ! J\'ai déjà versé ma cotisation ✅', 1],
                ['Super ! Moi aussi, tout est dans Orange Money 💰', 2],
                ['Quand est-ce que le tirage est prévu ?', 1],
                ['Dans 3 jours, le 15. Restez connectés 📅', 0],
                ['Parfait, je serai là 👌', 2],
            ];
            $count = 0;
            foreach ($msgs as $i => [$msg, $mi]) {
                $exists = ChatMessage::where('tontine_id', $tontine->id)->where('message', $msg)->exists();
                if (!$exists) {
                    ChatMessage::create([
                        'tontine_id' => $tontine->id, 'user_id' => $members[$mi],
                        'message' => $msg, 'created_at' => $this->now->copy()->subDays(7 - $i),
                    ]);
                    $count++;
                }
            }
            $this->command?->info("  ✓ $count messages dans {$tontine->name}");
        }
    }

    private function awardBadgesAndScores(): void
    {
        $this->command?->info("\n--- Badges & Scores ---");

        // Payer les streaks pour que les badges soient attribuables
        User::whereIn('email', [
            'admin@tontinesn.test', 'fatou@tontinesn.test', 'tessier@tontinesn.test',
            'rbousquet@tontinesn.test', 'hugues@tontinesn.test', 'pierre@tontinesn.test',
        ])->update(['payment_streak' => 8, 'max_streak' => 10]);

        User::whereIn('email', [
            'membre@tontinesn.test', 'nbonnin@tontinesn.test', 'jlemonnier@tontinesn.test',
            'ilaurent@tontinesn.test', 'roland@tontinesn.test',
        ])->update(['payment_streak' => 5, 'max_streak' => 7]);

        User::whereIn('email', [
            'hcarre@tontinesn.test', 'npaul@tontinesn.test', 'bleroy@tontinesn.test',
            'juliette@tontinesn.test', 'maurice@tontinesn.test',
        ])->update(['payment_streak' => 3, 'max_streak' => 4]);

        $gamification = app(GamificationService::class);
        $scoring = app(CreditScoringService::class);

        foreach ($this->users as $email => $user) {
            $badges = $gamification->checkAndAwardBadges($user);
            if ($badges->isNotEmpty()) {
                $this->command?->info("  🏅 $email : " . $badges->pluck('slug')->implode(', '));
            }
        }

        $count = 0;
        foreach ($this->users as $user) {
            $scoring->calculate($user);
            $count++;
        }
        $this->command?->info("  ✓ Scores recalculés pour $count utilisateurs");
    }

    private function attachMembers(Tontine $tontine, array $emails): void
    {
        $data = [];
        foreach ($emails as $i => $email) {
            $user = $this->user($email);
            if (!$user) continue;
            $data[$user->id] = [
                'status' => 'active', 'position' => $i + 1,
                'joined_at' => $this->now->copy()->subDays(30 - $i * 3),
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
            'draw_hash' => $drawnAt ? hash('sha256', $t->code . "-$num-{$benef?->id}-" . $drawnAt->timestamp) : null,
            'drawn_at' => $drawnAt,
        ]);
    }

    private function payAll(Cycle $cycle, array $emails, int $amount, Carbon $paidAt): void
    {
        foreach ($emails as $email) {
            Transaction::create([
                'cycle_id' => $cycle->id, 'user_id' => $this->user($email)->id,
                'amount' => $amount, 'method' => 'wave', 'status' => 'success',
                'paid_at' => $paidAt, 'type' => 'cycle_payment',
            ]);
        }
    }
}

<?php
declare(strict_types=1);

/*
 * ============================================================
 * SEEDER DÉMO — DONNÉES DE PRÉSENTATION
 * ============================================================
 * Ajoute TOUT ce qui manque pour démontrer toutes les
 * fonctionnalités : parrainage, types tontine, QR P2P, badges,
 * vetos, enchères, chat.
 *
 * Usage : php artisan db:seed --class=DemoDataSeeder
 *         (ou : php demo_seeder.php pour le standalone)
 * ============================================================
 */

namespace Database\Seeders;

use App\Models\AuctionBid;
use App\Models\Badge;
use App\Models\ChatMessage;
use App\Models\CreditScore;
use App\Models\Cycle;
use App\Models\CycleVeto;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CreditScoringService;
use App\Services\GamificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    private const MEMBRE_PASSWORD = 'Membre2024!';

    public function run(): void
    {
        $this->command?->info('=== SEEDER DÉMO COMPLET ===');

        /* -------------------------------------------------------
         * 1. CRÉATION DE FATOU SI ABSENTE
         * ------------------------------------------------------- */
        $fatou = User::where('email', 'fatou@tontinesn.test')->first();
        if (!$fatou) {
            $fatou = User::create([
                'email'         => 'fatou@tontinesn.test',
                'name'          => 'Fatou Diallo',
                'phone_number'  => '+221 77 111 22 33',
                'password'      => bcrypt(self::MEMBRE_PASSWORD),
                'role'          => 'member',
                'kyc_verified'  => true,
                'is_active'     => true,
                'created_at'    => now()->subMonths(6),
            ]);
            $this->command?->info('  ✓ fatou@tontinesn.test créé');
        } else {
            $this->command?->info('  ✓ fatou@tontinesn.test existe déjà');
        }

        /* -------------------------------------------------------
         * 2. CHAÎNE DE PARRAINAGE RÉALISTE
         * -------------------------------------------------------
         * admin (ID 1) → gestionnaire (ID 2) → manager (ID 3) → 10 filleuls
         * manager → fatou, membre, tessier, rbousquet, nbonnin,
         *           jlemonnier, lejeune, hugues, pierre, maryse
         * membre → parent.francoise (ID 15)
         * ------------------------------------------------------- */
        $this->seedReferrals();

        /* -------------------------------------------------------
         * 3. TONTINES DES TYPES MANQUANTS
         * ------------------------------------------------------- */
        $this->createWeightedVetoTontine();
        $this->createAuctionTontine();
        $this->createForcedSavingTontine();
        $this->createCeremonialTontine();

        /* -------------------------------------------------------
         * 4. TRANSACTIONS QR P2P
         * ------------------------------------------------------- */
        $this->createP2PTransactions();

        /* -------------------------------------------------------
         * 5. MESSAGES CHAT
         * ------------------------------------------------------- */
        $this->createChatMessages();

        /* -------------------------------------------------------
         * 6. BADGES & SCORES
         * ------------------------------------------------------- */
        $this->awardBadgesAndRecalculateScores();

        $this->command?->info('✓ TERMINÉ — Toutes les données de démo sont prêtes.');
    }

    /* ==========================================================
     *  PARRAINAGE
     * ========================================================== */
    private function seedReferrals(): void
    {
        $this->command?->info("\n--- Parrainage ---");

        // admin (ID 1) parraine gestionnaire (ID 2)
        $this->setReferral(2, 1);
        // gestionnaire (ID 2) parraine manager (ID 3)
        $this->setReferral(3, 2);

        // manager (ID 3) = SUPER PARRAIN (10 filleuls → badge Ambassadeur Or)
        $managerFilleuls = [
            4,   // membre@tontinesn.test
            5,   // tessier.etienne@example.com
            6,   // rbousquet@example.org
            7,   // nbonnin@example.org
            8,   // jlemonnier@example.org
            82,  // lejeune.eugene@example.com
            13,  // hugues36@example.org
            14,  // pierre06@example.com
            86,  // maryse.laporte@example.net
        ];
        // Ajouter fatou à la liste des filleuls de manager
        $fatou = User::where('email', 'fatou@tontinesn.test')->first();
        if ($fatou) {
            $managerFilleuls[] = $fatou->id;
        }

        foreach ($managerFilleuls as $userId) {
            $this->setReferral($userId, 3);
        }

        // membre (ID 4) parraine parent.francoise (ID 15)
        $this->setReferral(15, 4);

        // tessier (ID 5) parraine 2 personnes
        $this->setReferral(16, 5);  // hcarre@example.com
        $this->setReferral(25, 5);  // lacombe.franck@example.com

        $this->command?->info('  ✓ Chaînes de parrainage créées');
    }

    private function setReferral(int $userId, int $referrerId): void
    {
        User::where('id', $userId)->whereNull('referred_by')
            ->update(['referred_by' => $referrerId]);
    }

    /* ==========================================================
     *  TONTINE TIRAGE POIDÉ + VÉTO
     * ========================================================== */
    private function createWeightedVetoTontine(): void
    {
        $this->command?->info("\n--- Tontine Tirage avec Véto ---");

        $tontine = Tontine::firstOrCreate(
            ['name' => 'Tontine Castors Tirage'],
            [
                'code'           => 'CAS001',
                'description'    => 'Tontine avec tirage au sort pondéré par le score crédit et droit de véto (2 requis pour bloquer).',
                'amount'         => 50000,
                'frequency'      => 'monthly',
                'type'           => 'fixed',
                'status'         => 'active',
                'start_date'     => now()->subMonth(),
                'max_members'    => 6,
                'quorum'         => 60,
                'draw_method'    => 'random',
                'weighted_draw'  => true,
                'veto_threshold' => 33, // 33% des membres (soit 2) nécessaire pour véto
                'created_by'     => 4, // membre@tontinesn.test
            ]
        );

        if ($tontine->wasRecentlyCreated) {
            // Membres : créateur + 5 participants actifs de la base
            $memberIds = [4, 3, 5, 13, 14, 82]; // membre, manager, tessier, hugues, pierre, lejeune
            $this->syncMembers($tontine, $memberIds);

            // Cycle #1 (en cours, avec bénéficiaire assigné)
            $cycle1 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 1,
                'beneficiary_id' => 82,  // lejeune.eugene (tiré)
                'due_date'       => now()->addDays(10),
                'status'         => 'partial',
                'total_collected'=> 200000, // 4/6 ont payé
                'draw_hash'      => hash('sha256', 'cas001-1-82-' . now()->timestamp),
                'drawn_at'       => now()->subDays(2),
            ]);

            // Paiements pour le cycle #1 (4 succès sur 6)
            $payers = [4, 3, 5, 13]; // membre, manager, tessier, hugues
            foreach ($payers as $uid) {
                Transaction::create([
                    'cycle_id' => $cycle1->id,
                    'user_id'  => $uid,
                    'amount'   => 50000,
                    'method'   => 'orange_money',
                    'status'   => 'success',
                    'paid_at'  => now()->subDays(3),
                ]);
            }

            // 2 vetos sur le cycle #1 (de ceux qui n'ont pas payé, mécontents du tirage)
            $vetoMembers = [14, 82]; // pierre, lejeune (le bénéficiaire ne peut pas veto)
            foreach ($vetoMembers as $uid) {
                CycleVeto::firstOrCreate(['cycle_id' => $cycle1->id, 'user_id' => $uid]);
            }

            // Cycle #2 (en attente)
            Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 2,
                'due_date'       => now()->addDays(40),
                'status'         => 'pending',
                'total_collected'=> 0,
            ]);

            $this->command?->info('  ✓ Tontine Castors Tirage créée avec 1 cycle partiel + 2 vetos');
        } else {
            $this->command?->info('  ✓ Tontine Castors Tirage existe déjà');
        }
    }

    /* ==========================================================
     *  TONTINE ENCHÈRES
     * ========================================================== */
    private function createAuctionTontine(): void
    {
        $this->command?->info("\n--- Tontine Enchères ---");

        $tontine = Tontine::firstOrCreate(
            ['name' => 'Tontine Enchères Liberté'],
            [
                'code'           => 'ENC001',
                'description'    => 'Tontine aux enchères — le taux le plus élevé remporte le pot (0.5% à 30%).',
                'amount'         => 30000,
                'frequency'      => 'monthly',
                'type'           => 'auction',
                'status'         => 'active',
                'start_date'     => now()->subWeek(),
                'max_members'    => 5,
                'quorum'         => 1,
                'draw_method'    => 'sequential',
                'weighted_draw'  => false,
                'created_by'     => 6, // rbousquet@example.org
            ]
        );

        if ($tontine->wasRecentlyCreated) {
            $memberIds = [6, 40, 49, 56, 68]; // créateur + npaul, bleroy, juliette.carlier, maurice.arnaude
            $this->syncMembers($tontine, $memberIds);

            // Cycle #1 (actif, enchères en cours)
            $cycle1 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 1,
                'due_date'       => now()->addDays(14),
                'status'         => 'pending',
                'total_collected'=> 150000, // pot total
            ]);

            // Enchères soumises (taux variés 0.5% → 15%)
            $bids = [
                6  => 15.00, // rbousquet (créateur) — va gagner
                40 => 8.50,  // npaul
                49 => 12.00, // bleroy
                56 => 5.00,  // juliette.carlier
                68 => 2.00,  // maurice.arnaude
            ];
            foreach ($bids as $uid => $rate) {
                AuctionBid::firstOrCreate(
                    ['cycle_id' => $cycle1->id, 'user_id' => $uid],
                    ['bid_rate' => $rate]
                );
            }

            $this->command?->info('  ✓ Tontine Enchères Liberté créée avec 1 cycle + 5 offres');
        } else {
            $this->command?->info('  ✓ Tontine Enchères Liberté existe déjà');
        }
    }

    /* ==========================================================
     *  TONTINE ÉPARGNE FORCÉE
     * ========================================================== */
    private function createForcedSavingTontine(): void
    {
        $this->command?->info("\n--- Tontine Épargne Forcée ---");

        $tontine = Tontine::firstOrCreate(
            ['name' => 'Tontine Épargne HLM'],
            [
                'code'           => 'EPG001',
                'description'    => 'Épargne forcée — cotisation mensuelle bloquée, récupérée en fin de cycle.',
                'amount'         => 20000,
                'frequency'      => 'monthly',
                'type'           => 'forced_saving',
                'status'         => 'active',
                'start_date'     => now()->subMonths(2),
                'max_members'    => 4,
                'quorum'         => 1,
                'draw_method'    => 'sequential',
                'weighted_draw'  => false,
                'created_by'     => 5, // tessier.etienne@example.com
            ]
        );

        if ($tontine->wasRecentlyCreated) {
            $memberIds = [5, 11, 16, 24]; // tessier, ilaurent, hcarre, roland.martinez
            $this->syncMembers($tontine, $memberIds);

            // Cycle #1 (payé)
            $cycle1 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 1,
                'beneficiary_id' => 5,   // tessier
                'due_date'       => now()->subWeeks(5),
                'status'         => 'paid',
                'total_collected'=> 80000,
                'drawn_at'       => now()->subWeeks(5),
            ]);
            foreach ($memberIds as $uid) {
                Transaction::create([
                    'cycle_id' => $cycle1->id,
                    'user_id'  => $uid,
                    'amount'   => 20000,
                    'method'   => 'cash',
                    'status'   => 'success',
                    'paid_at'  => now()->subWeeks(5),
                ]);
            }

            // Cycle #2 (payé)
            $cycle2 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 2,
                'beneficiary_id' => 11,  // ilaurent
                'due_date'       => now()->subWeek(),
                'status'         => 'paid',
                'total_collected'=> 80000,
                'drawn_at'       => now()->subWeek(),
            ]);
            foreach ($memberIds as $uid) {
                Transaction::create([
                    'cycle_id' => $cycle2->id,
                    'user_id'  => $uid,
                    'amount'   => 20000,
                    'method'   => 'wave',
                    'status'   => 'success',
                    'paid_at'  => now()->subWeek(),
                ]);
            }

            // Cycle #3 (en cours, partiel)
            $cycle3 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 3,
                'due_date'       => now()->addDays(23),
                'status'         => 'partial',
                'total_collected'=> 40000, // 2/4 payés
            ]);
            $partialPayers = [5, 24]; // tessier, roland
            foreach ($partialPayers as $uid) {
                Transaction::create([
                    'cycle_id' => $cycle3->id,
                    'user_id'  => $uid,
                    'amount'   => 20000,
                    'method'   => 'orange_money',
                    'status'   => 'success',
                    'paid_at'  => now()->subDays(2),
                ]);
            }

            $this->command?->info('  ✓ Tontine Épargne HLM créée avec 2 cycles payés + 1 partiel');
        } else {
            $this->command?->info('  ✓ Tontine Épargne HLM existe déjà');
        }
    }

    /* ==========================================================
     *  TONTINE CÉRÉMONIAL
     * ========================================================== */
    private function createCeremonialTontine(): void
    {
        $this->command?->info("\n--- Tontine Cérémonial ---");

        $tontine = Tontine::firstOrCreate(
            ['name' => 'Tontine Cérémonial Sandaga'],
            [
                'code'           => 'CER001',
                'description'    => 'Cagnotte cérémoniale pour les grands événements (baptêmes, mariages). Cotisation unique à 100 000 F.',
                'amount'         => 100000,
                'frequency'      => 'monthly',
                'type'           => 'ceremonial',
                'status'         => 'active',
                'start_date'     => now()->subMonth(),
                'max_members'    => 4,
                'quorum'         => 1,
                'draw_method'    => 'sequential',
                'weighted_draw'  => false,
                'created_by'     => 3, // manager@tontinesn.test
            ]
        );

        if ($tontine->wasRecentlyCreated) {
            $memberIds = [3, 4, 5, 82]; // manager, membre, tessier, lejeune
            $this->syncMembers($tontine, $memberIds);

            // Cycle #1 (payé — cérémonie de Fatou)
            $cycle1 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 1,
                'beneficiary_id' => 3,   // manager
                'due_date'       => now()->subWeeks(2),
                'status'         => 'paid',
                'total_collected'=> 400000,
                'drawn_at'       => now()->subWeeks(2),
            ]);
            foreach ($memberIds as $uid) {
                Transaction::create([
                    'cycle_id' => $cycle1->id,
                    'user_id'  => $uid,
                    'amount'   => 100000,
                    'method'   => 'orange_money',
                    'status'   => 'success',
                    'paid_at'  => now()->subWeeks(2),
                ]);
            }

            // Cycle #2 (en cours)
            $cycle2 = Cycle::create([
                'tontine_id'     => $tontine->id,
                'cycle_number'   => 2,
                'due_date'       => now()->addDays(14),
                'status'         => 'pending',
                'total_collected'=> 0,
            ]);

            $this->command?->info('  ✓ Tontine Cérémonial Sandaga créée avec 1 cycle payé + 1 en attente');
        } else {
            $this->command?->info('  ✓ Tontine Cérémonial Sandaga existe déjà');
        }
    }

    /* ==========================================================
     *  TRANSACTIONS QR P2P
     * ========================================================== */
    private function createP2PTransactions(): void
    {
        $this->command?->info("\n--- Transactions QR P2P ---");

        // P2P #1 : membre → manager (15 000 F)
        $exists1 = Transaction::where('type', 'qr_p2p')
            ->where('user_id', 4)
            ->where('amount', 15000)
            ->exists();
        if (!$exists1) {
            Transaction::create([
                'cycle_id'  => null,
                'user_id'   => 4,    // membre
                'amount'    => 15000,
                'method'    => 'direct_transfer',
                'type'      => 'qr_p2p',
                'description'=> 'Paiement QR P2P — remboursement course',
                'metadata'  => json_encode(['sender_email' => 'membre@tontinesn.test', 'receiver_email' => 'manager@tontinesn.test', 'reason' => 'remboursement']),
                'status'    => 'success',
                'paid_at'   => now()->subDays(3),
            ]);
        }

        // P2P #2 : lejeune → pierre06 (25 000 F)
        $exists2 = Transaction::where('type', 'qr_p2p')
            ->where('user_id', 82)
            ->where('amount', 25000)
            ->exists();
        if (!$exists2) {
            Transaction::create([
                'cycle_id'  => null,
                'user_id'   => 82,   // lejeune.eugene
                'amount'    => 25000,
                'method'    => 'direct_transfer',
                'type'      => 'qr_p2p',
                'description'=> 'Paiement QR P2P — cotisation repas de famille',
                'metadata'  => json_encode(['sender_email' => 'lejeune.eugene@example.com', 'receiver_email' => 'pierre06@example.com', 'reason' => 'repas famille']),
                'status'    => 'success',
                'paid_at'   => now()->subDays(1),
            ]);
        }

        // P2P #3 : hugues36 → maryse (10 000 F, free_money pour montrer le method)
        $exists3 = Transaction::where('type', 'qr_p2p')
            ->where('user_id', 13)
            ->where('amount', 10000)
            ->exists();
        if (!$exists3) {
            Transaction::create([
                'cycle_id'  => null,
                'user_id'   => 13,   // hugues36
                'amount'    => 10000,
                'method'    => 'free_money',
                'type'      => 'qr_p2p',
                'description'=> 'Paiement QR P2P — cadeau anniversaire',
                'metadata'  => json_encode(['sender_email' => 'hugues36@example.org', 'receiver_email' => 'maryse.laporte@example.net', 'reason' => 'anniversaire']),
                'status'    => 'success',
                'paid_at'   => now()->subHours(12),
            ]);
        }

        // P2P #4 : fatou → manager (50 000 F — montant élevé pour montrer le KYC threshold)
        $fatou = User::where('email', 'fatou@tontinesn.test')->first();
        if ($fatou) {
            $exists4 = Transaction::where('type', 'qr_p2p')
                ->where('user_id', $fatou->id)
                ->where('amount', 50000)
                ->exists();
            if (!$exists4) {
                Transaction::create([
                    'cycle_id'  => null,
                    'user_id'   => $fatou->id,
                    'amount'    => 50000,
                    'method'    => 'direct_transfer',
                    'type'      => 'qr_p2p',
                    'description'=> 'Paiement QR P2P — contribution tontine cérémoniale',
                    'metadata'  => json_encode(['sender_email' => 'fatou@tontinesn.test', 'receiver_email' => 'manager@tontinesn.test', 'reason' => 'contribution ceremoniale']),
                    'status'    => 'success',
                    'paid_at'   => now()->subHours(6),
                ]);
            }
        }

        $this->command?->info('  ✓ 4 transactions QR P2P créées');
    }

    /* ==========================================================
     *  MESSAGES CHAT
     * ========================================================== */
    private function createChatMessages(): void
    {
        $this->command?->info("\n--- Messages Chat ---");

        $tontines = Tontine::whereIn('id', [3, 12])->get(); // Sandaga S2, Ouakam

        foreach ($tontines as $tontine) {
            $existingCount = ChatMessage::where('tontine_id', $tontine->id)->count();
            if ($existingCount > 2) {
                $this->command?->info("  ✓ {$tontine->name} a déjà $existingCount messages");
                continue;
            }

            $members = $tontine->activeMembers->pluck('id')->toArray();
            if (count($members) < 3) continue;

            $messages = [
                ['Bonjour à tous ! Prêt pour le nouveau cycle ? 💪', $members[0]],
                ['Présent ! J\'ai déjà versé ma cotisation ✅', $members[1]],
                ['Super ! Moi aussi, tout est dans Orange Money 💰', $members[2]],
                ['Quand est-ce que le tirage est prévu ?', $members[1]],
                ['Dans 3 jours, le 15. Restez connectés 📅', $members[0]],
                ['Parfait, je serai là 👌', $members[2]],
            ];

            foreach ($messages as $i => [$msg, $userId]) {
                $exists = ChatMessage::where('tontine_id', $tontine->id)
                    ->where('user_id', $userId)
                    ->where('message', $msg)
                    ->exists();
                if (!$exists) {
                    ChatMessage::create([
                        'tontine_id'  => $tontine->id,
                        'user_id'     => $userId,
                        'message'     => $msg,
                        'created_at'  => now()->subDays(7 - $i),
                    ]);
                }
            }
            $this->command?->info("  ✓ 6 messages ajoutés dans {$tontine->name}");
        }
    }

    /* ==========================================================
     *  BADGES & RECALCUL SCORES
     * ========================================================== */
    private function awardBadgesAndRecalculateScores(): void
    {
        $this->command?->info("\n--- Badges & Scores ---");

        $gamification = app(GamificationService::class);
        $scoring = app(CreditScoringService::class);

        // Forcer le recalcul des stats : mettre à jour les payment_streak
        // pour ceux qui ont beaucoup de paiements
        $this->bootstrapPaymentStreaks();

        // Attribuer les badges aux utilisateurs clés
        $keyUsers = [1, 2, 3, 4, 5, 6, 7, 8, 13, 14, 15, 82, 86];
        foreach ($keyUsers as $uid) {
            $user = User::find($uid);
            if (!$user) continue;

            $earned = $gamification->checkAndAwardBadges($user);
            if ($earned->isNotEmpty()) {
                $this->command?->info("  ✓ {$user->email} : badges attribués → " . $earned->pluck('slug')->implode(', '));
            }
        }

        // Recalculer les scores crédit pour TOUS les utilisateurs
        $count = 0;
        User::where('is_active', true)->chunk(50, function ($users) use ($scoring, &$count) {
            foreach ($users as $user) {
                $scoring->calculate($user);
                $count++;
            }
        });
        $this->command?->info("  ✓ Scores recalculés pour $count utilisateurs");
    }

    private function bootstrapPaymentStreaks(): void
    {
        // Donner un payment_streak cohérent aux utilisateurs avec beaucoup de paiements
        User::whereIn('id', [3, 5, 13, 14, 82, 86, 91])
            ->update(['payment_streak' => 5, 'max_streak' => 8]);
        User::whereIn('id', [4, 6, 15, 16])
            ->update(['payment_streak' => 3, 'max_streak' => 5]);
        User::whereIn('id', [25, 40, 41, 43, 49, 56, 65, 66, 68, 94])
            ->update(['payment_streak' => 2, 'max_streak' => 4]);
    }

    /* ==========================================================
     *  UTILITAIRE : SYNC MEMBRES
     * ========================================================== */
    private function syncMembers(Tontine $tontine, array $userIds): void
    {
        $now = now();
        $data = [];
        foreach ($userIds as $i => $uid) {
            $data[$uid] = [
                'status'             => 'active',
                'position'           => $i + 1,
                'joined_at'          => $now->copy()->subDays(30 - $i * 5),
                'start_cycle_number' => 1,
            ];
        }
        $tontine->members()->syncWithoutDetaching($data);
    }
}

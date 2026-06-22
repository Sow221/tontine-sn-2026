<?php

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Tontine;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $pw = env('SEED_MEMBRE_PASSWORD', 'Membre2024!');
        $adminPw = env('SEED_ADMIN_PASSWORD', 'Admin2024!');
        $this->now = now();

        $this->command?->info('=== CRÉATION/MISE À JOUR DES UTILISATEURS ===');

        // ── Admin ──
        $admin = $this->create('admin@tontinesn.test', 'Administrateur', '+221 77 000 00 00',
            $adminPw, 'super_admin', 'approved', true, 6);

        // ── Membres principaux (comptes de démo) ──
        $fatou = $this->create('fatou@tontinesn.test', 'Fatou Diallo', '+221 77 111 22 33',
            $pw, 'member', 'approved', true, 6);
        $membre = $this->create('membre@tontinesn.test', 'Moussa Ndiaye', '+221 76 444 55 66',
            $pw, 'member', 'approved', true, 5);

        // ── Membre 3 : Manager / super parrain ──
        $manager = $this->create('manager@tontinesn.test', 'Ibrahima Sow', '+221 77 123 45 67',
            $pw, 'member', 'approved', true, 6);
        $manager->update(['referred_by' => $admin->id]);

        // ── 10 filleuls du manager ──
        $filleuls = [
            ['tessier@tontinesn.test', 'Amadou Touré', '+221 76 111 11 11', 'approved', true, 5],
            ['rbousquet@tontinesn.test', 'Mamadou Lamine Ba', '+221 77 222 22 22', 'approved', true, 4],
            ['nbonnin@tontinesn.test', 'Aïssatou Sall', '+221 76 333 33 33', 'approved', true, 4],
            ['jlemonnier@tontinesn.test', 'Khadidiatou Diallo', '+221 77 444 44 44', 'approved', true, 3],
            ['hugues@tontinesn.test', 'Oumar Sy', '+221 76 555 55 55', 'approved', true, 3],
            ['pierre@tontinesn.test', 'Babacar Ndiaye', '+221 77 666 66 66', 'approved', true, 2],
            ['maryse@tontinesn.test', 'Seynabou Niang', '+221 76 777 77 77', 'none', true, 2],
            ['ilaurent@tontinesn.test', 'Maimouna Gueye', '+221 77 888 88 88', 'approved', true, 3],
            ['hcarre@tontinesn.test', 'El Hadj Diop', '+221 76 999 99 99', 'rejected', true, 2],
            ['roland@tontinesn.test', 'Pape Sène', '+221 77 101 01 01', 'none', true, 1],
        ];
        foreach ($filleuls as $i => [$email, $name, $phone, $kyc, $active, $months]) {
            $u = $this->create($email, $name, $phone, $pw, 'member', $kyc, $active, $months);
            $u->update(['referred_by' => $manager->id]);
        }
        $fatou->update(['referred_by' => $manager->id]);
        $membre->update(['referred_by' => $manager->id]);

        // ── Membre parraine un filleul ──
        $this->create('parent.francoise@tontinesn.test', 'Fatou Bintou Ba', '+221 77 202 02 02',
            $pw, 'member', 'none', true, 2)->update(['referred_by' => $membre->id]);

        // ── Tessier parraine 2 personnes ──
        $tessier = User::where('email', 'tessier@tontinesn.test')->first();
        $this->create('lacombe.franck@tontinesn.test', 'Youssoupha Mbaye', '+221 76 303 03 03',
            $pw, 'member', 'approved', true, 4)->update(['referred_by' => $tessier?->id]);
        $this->create('blanchet.amedee@tontinesn.test', 'Thierno Seydi', '+221 77 404 04 04',
            $pw, 'member', 'none', true, 1)->update(['referred_by' => $tessier?->id]);

        // ── 20 autres membres avec variété de profils ──
        $autres = [
            ['npaul@tontinesn.test', 'Ndèye Sow', '+221 76 505 05 05', 'approved', true, 4],
            ['bleroy@tontinesn.test', 'Abdoulaye Thiam', '+221 77 606 06 06', 'approved', true, 3],
            ['juliette@tontinesn.test', 'Coumba Faye', '+221 76 707 07 07', 'approved', true, 2],
            ['maurice@tontinesn.test', 'Idrissa Dieng', '+221 77 808 08 08', 'none', true, 2],
            ['lejeune.eugene@tontinesn.test', 'Eugène Ndiaye', '+221 76 909 09 09', 'rejected', true, 1],
            ['chretien.joseph@tontinesn.test', 'Joseph Mendy', '+221 77 010 10 10', 'none', true, 1],
            ['diaz.salome@tontinesn.test', 'Arame Sall', '+221 76 111 12 12', 'approved', true, 3],
            ['renaud.nicolas@tontinesn.test', 'Souleymane Kane', '+221 77 222 23 23', 'none', true, 1],
            ['leclerc.sabine@tontinesn.test', 'Diarra Ba', '+221 76 333 34 34', 'none', true, 2],
            ['gilles.guillaume@tontinesn.test', 'Modou Fall', '+221 77 444 45 45', 'approved', true, 3],
            ['royer.jean@tontinesn.test', 'Daouda Sall', '+221 76 555 56 56', 'none', true, 1],
            ['menard.chantal@tontinesn.test', 'Rokhaya Fall', '+221 77 666 67 67', 'rejected', true, 2],
            ['leroux.alexandre@tontinesn.test', 'Cheikh Ndiaye', '+221 76 777 78 78', 'approved', true, 3],
            ['morin.sylvie@tontinesn.test', 'Khady Ndiaye', '+221 77 888 89 89', 'none', true, 1],
            ['fournier.amedee@tontinesn.test', 'Mamour Diagne', '+221 76 999 90 90', 'none', true, 2],
            ['garnier.alphonse@tontinesn.test', 'Aly Cissé', '+221 77 101 11 11', 'approved', true, 3],
            ['chevalier.lucie@tontinesn.test', 'Absa Ndao', '+221 76 212 12 12', 'none', true, 1],
            ['guerin.marc@tontinesn.test', 'Yoro Diallo', '+221 77 313 13 13', 'approved', true, 2],
            ['lemaitre.denis@tontinesn.test', 'Mactar Seye', '+221 76 414 14 14', 'none', true, 1],
            ['roux.sylvain@tontinesn.test', 'Serigne Sall', '+221 77 515 15 15', 'rejected', true, 1],
        ];
        foreach ($autres as [$email, $name, $phone, $kyc, $active, $months]) {
            $u = $this->create($email, $name, $phone, $pw, 'member', $kyc, $active, $months);
            // Ajouter des parrainages aléatoires pour enrichir l'arbre
            if (in_array($email, ['diaz.salome@tontinesn.test', 'leroux.alexandre@tontinesn.test',
                'garnier.alphonse@tontinesn.test', 'guerin.marc@tontinesn.test'])) {
                $u->update(['referred_by' => $fatou->id]);
            }
        }

        $total = User::count();
        $this->command?->info("  ✓ $total utilisateurs présents");

        // ── Badges ──
        $this->call(BadgeSeeder::class);

        // ── Données démo ──
        $this->call(DemoDataSeeder::class);

        // ── Résumé ──
        $this->command?->info('');
        $this->command?->info('╔══════════════════════════════════════════╗');
        $this->command?->info('║         COMPTES DE PRÉSENTATION        ║');
        $this->command?->info('╠══════════════════════════════════════════╣');
        $this->command?->info('║  Admin       admin@tontinesn.test       ║');
        $this->command?->info('║  Membre 1    fatou@tontinesn.test       ║');
        $this->command?->info('║  Membre 2    membre@tontinesn.test      ║');
        $this->command?->info('║  Manager     manager@tontinesn.test     ║');
        $this->command?->info('╠══════════════════════════════════════════╣');
        $this->command?->info('║  Mot de passe : Membre2024!             ║');
        $this->command?->info('║  (Admin: Admin2024!)                     ║');
        $this->command?->info('╚══════════════════════════════════════════╝');
        $this->command?->info('');
        $this->command?->info('stats → '.User::count().' users · '
            .Tontine::count().' tontines · '
            .Cycle::count().' cycles · '
            .Transaction::count().' transactions');
    }

    private function create(string $email, string $name, string $phone,
        string $password, string $role, string $kycStatus,
        bool $active, int $monthsAgo): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone_number' => $phone,
                'password' => bcrypt($password),
                'role' => $role,
                'kyc_status' => $kycStatus,
                'kyc_verified' => $kycStatus === 'approved',
                'is_active' => $active,
                'onboarding_completed' => true,
                'email_verified_at' => $this->now->copy()->subMonths($monthsAgo),
                'created_at' => $this->now->copy()->subMonths($monthsAgo),
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            ['slug' => 'first_payment',     'name' => 'Premier Pas',        'description' => 'Effectuer votre premier paiement',                       'icon' => '🌱', 'tier' => 'bronze', 'criteria_type' => 'first_payment',    'criteria_value' => 1],
            ['slug' => 'streak_5',           'name' => 'Régulier',          'description' => '5 paiements consécutifs à temps',                         'icon' => '🔥', 'tier' => 'bronze', 'criteria_type' => 'payment_streak',   'criteria_value' => 5],
            ['slug' => 'streak_10',          'name' => 'Fidèle',            'description' => '10 paiements consécutifs à temps',                        'icon' => '💎', 'tier' => 'silver', 'criteria_type' => 'payment_streak',   'criteria_value' => 10],
            ['slug' => 'streak_20',          'name' => 'Inébranlable',      'description' => '20 paiements consécutifs à temps',                        'icon' => '👑', 'tier' => 'gold',   'criteria_type' => 'payment_streak',   'criteria_value' => 20],
            ['slug' => 'beneficiary_3',      'name' => 'Collecteur',        'description' => 'Recevoir comme bénéficiaire 3 fois',                      'icon' => '🎁', 'tier' => 'bronze', 'criteria_type' => 'beneficiary_count','criteria_value' => 3],
            ['slug' => 'beneficiary_10',     'name' => 'Trésorier',         'description' => 'Recevoir comme bénéficiaire 10 fois',                     'icon' => '🏦', 'tier' => 'gold',   'criteria_type' => 'beneficiary_count','criteria_value' => 10],
            ['slug' => 'creator_3',          'name' => 'Meneur',            'description' => 'Créer 3 tontines',                                       'icon' => '🚀', 'tier' => 'silver', 'criteria_type' => 'tontines_created', 'criteria_value' => 3],
            ['slug' => 'on_time_3mois',      'name' => 'Ponctuel',          'description' => 'Aucun retard de paiement pendant 3 mois',                  'icon' => '⏰', 'tier' => 'silver', 'criteria_type' => 'on_time_months',   'criteria_value' => 3],
            ['slug' => 'tontine_completed',  'name' => 'Tontine d\'Or',     'description' => 'Participer du début à la fin d\'une tontine',             'icon' => '🏆', 'tier' => 'gold',   'criteria_type' => 'tontine_completed','criteria_value' => 1],
            ['slug' => 'inviter_5',          'name' => 'Ambassadeur',       'description' => 'Inviter 5 membres à rejoindre une tontine',                'icon' => '🤝', 'tier' => 'silver', 'criteria_type' => 'invited_members',  'criteria_value' => 5],
            // Badges parrainage
            ['slug' => 'referral_1',         'name' => 'Recruteur',         'description' => 'Parrainer votre premier membre',                         'icon' => '🌟', 'tier' => 'bronze', 'criteria_type' => 'referrals_count',  'criteria_value' => 1],
            ['slug' => 'referral_5',         'name' => 'Influenceur',       'description' => 'Parrainer 5 membres',                                    'icon' => '📣', 'tier' => 'silver', 'criteria_type' => 'referrals_count',  'criteria_value' => 5],
            ['slug' => 'referral_10',        'name' => 'Ambassadeur Or',    'description' => 'Parrainer 10 membres — vous êtes un pilier de la communauté','icon' => '🏅', 'tier' => 'gold',   'criteria_type' => 'referrals_count',  'criteria_value' => 10],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['slug' => $badge['slug']], $badge);
        }
    }
}

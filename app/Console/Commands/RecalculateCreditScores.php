<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Console\Command;

class RecalculateCreditScores extends Command
{
    protected $signature = 'tontine:recalculate-scores {--user= : Recalculer pour un utilisateur spécifique}';

    protected $description = 'Recalcule les scores de crédit pour tous les utilisateurs ou un utilisateur spécifique';

    public function handle(CreditScoringService $scoringService): int
    {
        if ($userId = $this->option('user')) {
            $user = User::find($userId);
            if (! $user) {
                $this->error('Utilisateur introuvable.');

                return Command::FAILURE;
            }
            $scoringService->calculate($user);
            $this->info("Score recalculé pour {$user->email}");

            return Command::SUCCESS;
        }

        $users = User::all();
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $scoringService->calculate($user);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Scores recalculés pour {$users->count()} utilisateurs.");

        return Command::SUCCESS;
    }
}

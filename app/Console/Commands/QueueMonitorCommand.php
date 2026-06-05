<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueMonitorCommand extends Command
{
    protected $signature   = 'queue:monitor';
    protected $description = 'Affiche l\'état de la queue (jobs en attente, en cours, échoués)';

    public function handle(): int
    {
        $pending = DB::table('jobs')->count();
        $failed  = DB::table('failed_jobs')->count();

        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Jobs en attente',  $pending],
                ['Jobs échoués',     $failed],
                ['Driver actif',     config('queue.default')],
            ]
        );

        if ($failed > 0) {
            $this->warn("⚠️  {$failed} job(s) échoué(s). Lancez : php artisan queue:retry all");
        }

        if ($pending === 0 && $failed === 0) {
            $this->info('✅ Queue propre — aucun job en attente ni en échec.');
        }

        return Command::SUCCESS;
    }
}

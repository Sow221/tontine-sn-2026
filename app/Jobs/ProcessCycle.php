<?php

namespace App\Jobs;

use App\Models\Tontine;
use App\Services\CycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCycle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(public Tontine $tontine) {}

    public function handle(CycleService $service): void
    {
        try {
            $service->createCycles($this->tontine);
            Log::info('Cycles créés avec succès', ['tontine_id' => $this->tontine->id]);
        } catch (\Throwable $e) {
            Log::error('Erreur création cycles', [
                'tontine_id' => $this->tontine->id,
                'error'      => $e->getMessage(),
            ]);
            throw $e; // permet le retry automatique
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessCycle job échoué définitivement', [
            'tontine_id' => $this->tontine->id,
            'error'      => $exception->getMessage(),
        ]);

        // Repasser la tontine en pending pour éviter état incohérent
        $this->tontine->update(['status' => 'pending']);
    }
}

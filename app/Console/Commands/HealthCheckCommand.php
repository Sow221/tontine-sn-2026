<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\GreenApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check {--ci : Format machine-readable pour CI}';

    protected $description = 'Vérifie l\'état de tous les services externes de TontineSN';

    private int $exitCode = Command::SUCCESS;

    public function handle(GreenApiService $greenApi): int
    {
        $ci = $this->option('ci');

        $this->check('Base de données', fn () => DB::statement('SELECT 1'));
        $this->check('Stockage (local)', fn () => Storage::disk('local')->put('health-check.txt', 'ok') && Storage::disk('local')->delete('health-check.txt'));
        $this->check('Stockage (public)', fn () => Storage::disk('public')->put('health-check.txt', 'ok') && Storage::disk('public')->delete('health-check.txt'));
        $this->check('Cache', fn () => cache()->set('health-check', 'ok', 1) && cache()->get('health-check') === 'ok' && cache()->forget('health-check'));
        $this->check('Google OAuth (config)', fn () => ! empty(config('services.google.client_id')) && ! empty(config('services.google.client_secret')));
        $this->checkPayTech();
        $this->checkMail();
        $this->checkGreenApi($greenApi);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Status</>', $this->exitCode === Command::SUCCESS ? '<fg=green;options=bold>TOUT OK</>' : '<fg=red;options=bold>ERREURS DÉTECTÉES</>');

        return $this->exitCode;
    }

    private function check(string $label, callable $test): void
    {
        $start = microtime(true);
        $ok = false;
        $error = '';

        try {
            $ok = (bool) $test();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $time = round((microtime(true) - $start) * 1000);

        if ($ok) {
            $this->components->twoColumnDetail("<fg=green>✓</> {$label}", "{$time}ms");
        } else {
            $this->components->twoColumnDetail("<fg=red>✗</> {$label}", "{$time}ms");
            if ($error) {
                $this->components->twoColumnDetail('<fg=gray>  └ raison</>', "<fg=red>{$error}</>");
            }
            $this->exitCode = Command::FAILURE;
        }
    }

    private function checkPayTech(): void
    {
        $hasConfig = ! empty(config('mobilemoney.paytech.api_key'))
            && ! empty(config('mobilemoney.paytech.api_secret'))
            && ! empty(config('mobilemoney.paytech.base_url'));

        if (! $hasConfig) {
            $this->components->twoColumnDetail('<fg=yellow>!</> PayTech', '<fg=yellow>non configuré</>');

            return;
        }

        $this->check('PayTech (config)', fn () => true);
    }

    private function checkMail(): void
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        if ($mailer && $from) {
            $this->check('Mail (config)', fn () => true);
        } else {
            $this->components->twoColumnDetail('<fg=yellow>!</> Mail (config)', '<fg=yellow>non configuré</>');
        }
    }

    private function checkGreenApi(GreenApiService $greenApi): void
    {
        if (! $greenApi->isConfigured()) {
            $this->components->twoColumnDetail('<fg=yellow>!</> Green API (WhatsApp)', '<fg=yellow>non configuré</>');

            return;
        }

        $this->check('Green API (WhatsApp)', fn () => $greenApi->getState() === 'authorized');
    }
}

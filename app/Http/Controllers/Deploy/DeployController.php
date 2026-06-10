<?php

namespace App\Http\Controllers\Deploy;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeployController
{
    public function migrate(Request $request)
    {
        $token = $request->query('token');

        if ($token !== 'tontine221_deploy_2024') {
            abort(403, 'Token invalide.');
        }

        $output = '';

        try {
            Artisan::call('migrate', ['--force' => true]);
            $output .= Artisan::output()."\n";

            Artisan::call('db:seed', ['--force' => true]);
            $output .= Artisan::output()."\n";

            Artisan::call('storage:link');
            $output .= Artisan::output()."\n";

            Log::info('Déploiement terminé avec succès');
        } catch (\Throwable $e) {
            $output .= 'ERREUR: '.$e->getMessage();
            Log::error('Erreur déploiement', ['error' => $e->getMessage()]);
        }

        return response('<pre>'.htmlspecialchars($output).'</pre>', 200)
            ->header('Content-Type', 'text/html');
    }
}

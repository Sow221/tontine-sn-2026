<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tontine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TontineService
{
    public function joinTontine(?Tontine $tontine, int $userId): array
    {
        if (!$tontine) {
            return ['ok' => false, 'message' => 'Code invalide. Vérifiez et réessayez.'];
        }
        if (!$tontine->acceptsNewMembers()) {
            return ['ok' => false, 'message' => 'Cette tontine n\'accepte plus de nouveaux membres (complète ou clôturée).'];
        }
        if ($tontine->members()->where('users.id', $userId)->whereIn('tontine_members.status', ['active', 'pending'])->exists()) {
            return ['ok' => false, 'message' => 'Vous êtes déjà membre de cette tontine.', 'already' => true];
        }
        if ($tontine->members()->where('users.id', $userId)->wherePivot('status', 'excluded')->exists()) {
            return ['ok' => false, 'message' => 'Vous ne pouvez pas rejoindre cette tontine.'];
        }

        $user  = User::find($userId);
        $score = $user?->creditScore?->score ?? 0;
        $kycThreshold    = config('tontine.transaction.kyc_threshold', 300_000);
        $kycDocThreshold = config('tontine.transaction.kyc_doc_threshold', 50_000);

        if ($tontine->amount >= $kycDocThreshold && !$user?->kyc_document) {
            return [
                'ok'        => false,
                'message'   => 'Veuillez soumettre un document d\'identité dans votre profil pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA ou plus.',
                'kyc_block' => true,
            ];
        }

        if ($tontine->amount >= $kycThreshold && !$user?->kyc_verified) {
            return [
                'ok'        => false,
                'message'   => 'Une vérification d\'identité (KYC) approuvée est requise pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA ou plus. Soumettez votre document dans votre profil.',
                'kyc_block' => true,
            ];
        }

        $hasHistory = $user?->transactions()->where('status', 'success')->exists();
        if ($hasHistory && $score < 2 && $tontine->amount > 50_000) {
            return [
                'ok'          => false,
                'message'     => 'Votre score crédit (' . $score . '/10) est insuffisant pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA. Améliorez votre score en payant à temps.',
                'score_block' => true,
            ];
        }

        // Déterminer le cycle de départ si la tontine est déjà active
        $startCycleNumber = 1;
        if ($tontine->status === 'active') {
            $currentCycleNumber = $tontine->cycles()
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->min('cycle_number');
            $startCycleNumber = $currentCycleNumber ?? 1;
        }

        \Log::info('TontineService joinTontine debug', [
            'tontine_id' => $tontine->id,
            'tontine_status' => $tontine->status,
            'startCycleNumber' => $startCycleNumber,
            'userId' => $userId,
        ]);

        $result = ['ok' => false, 'message' => ''];

        DB::transaction(function () use ($tontine, $userId, $startCycleNumber, &$result) {
            \Log::info('TontineService joinTontine inside transaction debug', [
                'startCycleNumber' => $startCycleNumber,
            ]);
            try {
            $locked = Tontine::lockForUpdate()->find($tontine->id);

            if ($locked->isFull()) {
                $result = ['ok' => false, 'message' => 'Cette tontine est complète.'];
                return;
            }

            $locked->members()->syncWithoutDetaching([
                $userId => [
                    'status'            => 'pending',
                    'position'          => $locked->activeMembers()->count() + 1,
                    'joined_at'         => now(),
                    'start_cycle_number'=> $startCycleNumber ?? 1,
                ],
            ]);

            $message = 'Demande d\'adhésion envoyée. En attente d\'approbation.';
            if ($startCycleNumber && $startCycleNumber > 1) {
                $message .= ' Vous commencerez à cotiser à partir du cycle ' . $startCycleNumber . '.';
            }

            $result = ['ok' => true, 'message' => $message];
        } catch (\Throwable $e) {
            \Log::error('TontineService joinTontine transaction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }
        });

        // Notifier le créateur de la nouvelle demande (hors transaction pour éviter les locks)
        if ($result['ok']) {
            $creator = $tontine->creator;
            $newMember = User::find($userId);
            if ($creator && $newMember && $creator->id !== $userId) {
                try {
                    app(\App\Services\NotificationService::class)
                        ->notifyNewMemberRequest($creator, $newMember, $tontine);
                } catch (\Throwable $e) {
                    Log::error('Erreur notification nouvelle demande', [
                        'error' => $e->getMessage(),
                        'class' => get_class($e),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    // Ne pas faire échouer l'adhésion pour une erreur de notification
                }
            }
        }

        return $result;
    }
}

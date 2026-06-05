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
        $kycThreshold    = config('tontine.transaction.kyc_threshold', 500_000);
        $kycDocThreshold = config('tontine.transaction.kyc_doc_threshold', 50_000);

        // Palier 1 : doc soumis requis (>= 50 000 FCFA)
        if ($tontine->amount >= $kycDocThreshold && !$user?->kyc_document) {
            return [
                'ok'        => false,
                'message'   => 'Veuillez soumettre un document d\'identité dans votre profil pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA ou plus.',
                'kyc_block' => true,
            ];
        }

        // Palier 2 : KYC vérifié requis (>= 500 000 FCFA)
        if ($tontine->amount >= $kycThreshold && !$user?->kyc_verified) {
            return [
                'ok'        => false,
                'message'   => 'Une vérification d\'identité (KYC) approuvée est requise pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA ou plus. Soumettez votre document dans votre profil.',
                'kyc_block' => true,
            ];
        }

        // Bloquer si score insuffisant (uniquement pour les utilisateurs ayant déjà un historique)
        $hasHistory = $user?->transactions()->where('status', 'success')->exists();
        if ($hasHistory && $score < 2 && $tontine->amount > 50_000) {
            return [
                'ok'          => false,
                'message'     => 'Votre score crédit (' . $score . '/10) est insuffisant pour rejoindre une tontine de '
                               . number_format($tontine->amount, 0, ',', ' ') . ' FCFA. Améliorez votre score en payant à temps.',
                'score_block' => true,
            ];
        }

        $result = ['ok' => false, 'message' => ''];

        DB::transaction(function () use ($tontine, $userId, &$result) {
            $locked = Tontine::lockForUpdate()->find($tontine->id);

            if ($locked->isFull()) {
                $result = ['ok' => false, 'message' => 'Cette tontine est complète.'];
                return;
            }

            $locked->members()->syncWithoutDetaching([
                $userId => [
                    'status'    => 'pending',
                    'position'  => $locked->members()->count() + 1,
                    'joined_at' => now(),
                ],
            ]);

            $result = ['ok' => true, 'message' => 'Demande d\'adhésion envoyée. En attente d\'approbation.'];
        });

        return $result;
    }
}

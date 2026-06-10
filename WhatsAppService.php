<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private $url = 'https://greenapi.com';

    private function send($to, $message)
    {
        return Http::post($this->url, [
            'chatId' => str_replace('+', '', $to).'@c.us',
            'message' => $message,
        ]);
    }

    public function sendPaymentReminder($to, $montant, $tontine, $jours, $link)
    {
        $msg = "🔔 *Rappel cotisation*\n\nVotre cotisation de {$montant} pour la tontine {$tontine} est due dans {$jours} jour(s). Payez à temps pour garder votre score crédit.\n\n👉 *Payer maintenant :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }

    public function sendPaymentOverdue($to, $montant, $tontine, $jours, $link)
    {
        $msg = "⚠️ *Cotisation en retard*\n\nVotre cotisation de {$montant} pour la tontine {$tontine} est en retard de {$jours} jour(s). Régularisez pour éviter les pénalités.\n\n👉 *Payer maintenant :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }

    public function sendBeneficiaryDrawn($to, $montant, $tontine, $link)
    {
        $msg = "🎉 *C'est votre tour !*\n\nFélicitations ! Vous êtes le bénéficiaire de la tontine {$tontine}.\nMontant à recevoir : *{$montant} FCFA*.\n\n👉 *Voir détails :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }

    public function sendPaymentConfirmed($to, $montant, $tontine, $link)
    {
        $msg = "✅ *Paiement confirmé*\n\nVotre paiement de {$montant} pour la tontine {$tontine} a bien été enregistré. Merci pour votre ponctualité !\n\n👉 *Télécharger reçu :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }

    public function sendMemberRequest($to, $nom, $tontine, $link)
    {
        $msg = "👤 *Nouvelle demande*\n\n*{$nom}* a demandé à rejoindre votre tontine {$tontine}.\n\n👉 *Approuver / Refuser :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }

    public function sendKycApproved($to, $link)
    {
        $msg = "✅ *Identité vérifiée*\n\nVotre identité a été vérifiée avec succès. Vous pouvez maintenant rejoindre toutes les tontines sans restriction.\n\n👉 *Voir tontines :* {$link}\n\n_TontineSN - Gestion tontines Sénégal_";

        return $this->send($to, $msg);
    }
}

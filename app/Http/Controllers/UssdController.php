<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CreditScoringService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UssdController extends Controller
{
    public function __construct(private CreditScoringService $scorer) {}

    public function handle(Request $request): Response
    {
        $sessionId = $request->input('sessionId');
        $phone     = $request->input('phoneNumber');
        $text      = $request->input('text', '');

        $user  = User::where('phone_number', $phone)->first();
        $parts = $text === '' ? [] : explode('*', $text);
        $level = count($parts);

        $response = match(true) {
            $level === 0                    => $this->mainMenu(),
            $level === 1 && $parts[0] === '1' => $this->myTontines($user),
            $level === 1 && $parts[0] === '2' => $this->payMenu($user),
            $level === 1 && $parts[0] === '3' => $this->beneficiaries($user),
            $level === 1 && $parts[0] === '4' => $this->history($user),
            $level === 1 && $parts[0] === '5' => $this->creditScore($user),
            $level === 1 && $parts[0] === '0' => $this->changeLanguage(),
            default                           => $this->mainMenu(),
        };

        return response($response, 200)->header('Content-Type', 'text/plain');
    }

    private function mainMenu(): string
    {
        return "CON TontineSN\nChoisissez :\n1. Mes tontines\n2. Payer cotisation\n3. Bénéficiaires\n4. Historique\n5. Mon score crédit\n0. Changer langue";
    }

    private function myTontines(?User $user): string
    {
        if (!$user) return "END Numéro non enregistré. Inscrivez-vous sur tontinesn.sn";

        $tontines = $user->memberships()->wherePivot('status', 'active')->get();

        if ($tontines->isEmpty()) return "END Vous n'avez aucune tontine active.";

        $list = $tontines->map(fn($t, $i) => ($i + 1) . ". {$t->name} - {$t->amount} FCFA")->join("\n");
        return "END Mes tontines :\n{$list}";
    }

    private function payMenu(?User $user): string
    {
        if (!$user) return "END Numéro non enregistré.";

        $next = $user->memberships()
            ->wherePivot('status', 'active')
            ->get()
            ->map(fn($t) => $t->currentCycle())
            ->filter()
            ->sortBy('due_date')
            ->first();

        if (!$next) return "END Aucun paiement en attente.";

        return "END Prochain paiement :\n{$next->tontine->name}\nMontant : {$next->tontine->amount} FCFA\nDate limite : {$next->due_date->format('d/m/Y')}\n\nPayez via Wave ou Orange Money sur tontinesn.sn";
    }

    private function beneficiaries(?User $user): string
    {
        if (!$user) return "END Numéro non enregistré.";

        $cycles = \App\Models\Cycle::whereHas('tontine.members', fn($q) => $q->where('users.id', $user->id))
            ->whereNotNull('beneficiary_id')
            ->with('beneficiary', 'tontine')
            ->latest()
            ->take(5)
            ->get();

        if ($cycles->isEmpty()) return "END Aucun bénéficiaire récent.";

        $list = $cycles->map(fn($c) => "{$c->tontine->name} → {$c->beneficiary->name}")->join("\n");
        return "END Bénéficiaires récents :\n{$list}";
    }

    private function history(?User $user): string
    {
        if (!$user) return "END Numéro non enregistré.";

        $txs = $user->transactions()->where('status', 'success')->latest()->take(5)->get();

        if ($txs->isEmpty()) return "END Aucune transaction.";

        $list = $txs->map(fn($t) => "{$t->paid_at->format('d/m')} - {$t->amount} FCFA ({$t->method})")->join("\n");
        return "END Historique :\n{$list}";
    }

    private function creditScore(?User $user): string
    {
        if (!$user) return "END Numéro non enregistré.";

        $score = $user->creditScore ?? $this->scorer->calculate($user);
        return "END Mon score crédit :\n{$score->score}/10\nBadge : {$score->badgeLabel()}\nPaiements à temps : {$score->on_time_payments}/{$score->total_cycles}";
    }

    private function changeLanguage(): string
    {
        return "CON Choisir langue :\n1. Français\n2. Wolof\n3. English";
    }
}

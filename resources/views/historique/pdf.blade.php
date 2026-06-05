<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique complet — TontineSN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; color: #1E293B; font-size: 12px; padding: 20px; }
        .header { text-align: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #009639; }
        .header h1 { color: #009639; font-size: 22px; margin-bottom: 4px; }
        .header p { color: #94A3B8; font-size: 13px; }
        .info { margin-bottom: 16px; font-size: 12px; color: #64748B; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th { background: #009639; color: white; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
        td { padding: 7px 10px; border-bottom: 1px solid #E2E8F0; font-size: 11px; }
        tr:nth-child(even) td { background: #F8FAFC; }
        .success { color: #009639; font-weight: 600; }
        .pending { color: #D97706; font-weight: 600; }
        .failed { color: #DC2626; font-weight: 600; }
        .footer { text-align: center; margin-top: 24px; padding-top: 16px; border-top: 1px solid #E2E8F0; font-size: 11px; color: #94A3B8; }
        .total-row td { font-weight: 700; background: #F0FDF4 !important; border-top: 2px solid #009639; }
    </style>
</head>
<body>
    <div class="header">
        <h1>TontineSN</h1>
        <p>Historique complet des transactions</p>
    </div>

    <div class="info">
        <strong>Membre :</strong> {{ $user->name }} &mdash; {{ $user->email }}<br>
        <strong>Généré le :</strong> {{ now()->isoFormat('D MMMM YYYY à HH:mm') }}
    </div>

    @if($transactions->isEmpty())
        <p style="text-align:center;color:#94A3B8;margin-top:40px;">Aucune transaction trouvée.</p>
    @else
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Tontine</th>
                <th>Cycle</th>
                <th>Montant (FCFA)</th>
                <th>Méthode</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
            <tr>
                <td>{{ $tx->paid_at?->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $tx->cycle->tontine->name ?? '—' }}</td>
                <td>Cycle {{ $tx->cycle->cycle_number ?? '—' }}</td>
                <td>{{ number_format($tx->amount, 0, ',', ' ') }}</td>
                <td>{{ match($tx->method) { 'wave' => 'Wave', 'orange_money' => 'Orange Money', 'free_money' => 'Free Money', 'card' => 'Carte bancaire', 'cash' => 'Espèces', default => ucfirst($tx->method) } }}</td>
                <td class="{{ $tx->status }}">{{ match($tx->status) { 'success' => 'Payé', 'pending' => 'En attente', 'failed' => 'Échoué', default => ucfirst($tx->status) } }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3" style="text-align:right;">Total</td>
                <td>{{ number_format($transactions->where('status', 'success')->sum('amount'), 0, ',', ' ') }} FCFA</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>© {{ date('Y') }} TontineSN — Document généré automatiquement</p>
    </div>
</body>
</html>

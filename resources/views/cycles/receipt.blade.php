<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reçu #{{ $transaction->id }} — TontineSN</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; color: #1E293B; }
        .receipt { max-width: 480px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        .receipt-header { background: #009639; padding: 24px 32px; text-align: center; }
        .receipt-header h1 { color: white; font-size: 22px; margin-bottom: 4px; }
        .receipt-header p { color: rgba(255,255,255,.8); font-size: 13px; }
        .receipt-status { text-align: center; padding: 24px; border-bottom: 1px dashed #E2E8F0; }
        .receipt-status .icon { font-size: 48px; margin-bottom: 8px; }
        .receipt-status .amount { font-size: 32px; font-weight: 700; color: #009639; }
        .receipt-status .label { color: #94A3B8; font-size: 13px; margin-top: 4px; }
        .receipt-body { padding: 24px 32px; }
        .receipt-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #F0F2F5; }
        .receipt-row:last-child { border-bottom: none; }
        .receipt-row .key { color: #94A3B8; font-size: 13px; }
        .receipt-row .val { font-weight: 600; font-size: 14px; text-align: right; }
        .receipt-footer { padding: 16px 32px; background: #F9FAFB; text-align: center; font-size: 12px; color: #94A3B8; }
        .badge-success { background: #E8F5E9; color: #009639; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        @media print {
            body { background: white; padding: 0; }
            .receipt { box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1>TontineSN</h1>
            <p>Reçu de paiement</p>
        </div>

        <div class="receipt-status">
            <div class="icon">✅</div>
            <div class="amount">{{ number_format($transaction->amount, 0, ',', ' ') }} FCFA</div>
            <div class="label">Paiement confirmé</div>
        </div>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="key">Référence</span>
                <span class="val">#{{ $transaction->id }}</span>
            </div>
            <div class="receipt-row">
                <span class="key">Tontine</span>
                <span class="val">{{ $transaction->cycle->tontine->name }}</span>
            </div>
            <div class="receipt-row">
                <span class="key">Cycle</span>
                <span class="val">Cycle {{ $transaction->cycle->cycle_number }}</span>
            </div>
            <div class="receipt-row">
                <span class="key">Membre</span>
                <span class="val">{{ $transaction->user->name }}</span>
            </div>
            <div class="receipt-row">
                <span class="key">Mode de paiement</span>
                <span class="val" style="display:flex;align-items:center;gap:8px;">
                    @php
                        $opImgs = [
                            'wave'         => 'images/logo wave.webp',
                            'orange_money' => 'images/logo orange money.webp',
                            'free_money'   => 'images/logo free money.svg',
                        ];
                        $opEmojis = [
                            'cash' => '💵',
                            'card' => '💳',
                        ];
                        $opLabel = match($transaction->method) {
                            'wave'         => 'Wave',
                            'orange_money' => 'Orange Money',
                            'free_money'   => 'Free Money',
                            'card'         => 'Carte bancaire',
                            'cash'         => 'Espèces',
                            default        => ucfirst($transaction->method),
                        };
                    @endphp
                    @if(isset($opImgs[$transaction->method]))
                    <img src="{{ asset($opImgs[$transaction->method]) }}" alt="{{ $opLabel }}" style="height:20px;width:auto;vertical-align:middle;">
                    @elseif(isset($opEmojis[$transaction->method]))
                    <span style="font-size:16px;">{{ $opEmojis[$transaction->method] }}</span>
                    @endif
                    {{ $opLabel }}
                </span>
            </div>
            <div class="receipt-row">
                <span class="key">Date</span>
                <span class="val">{{ $transaction->paid_at?->format('d/m/Y à H:i') ?? $transaction->created_at->format('d/m/Y à H:i') }}</span>
            </div>
            <div class="receipt-row">
                <span class="key">Statut</span>
                <span class="val"><span class="badge-success">Payé</span></span>
            </div>
        </div>

        <div class="receipt-footer">
            <p>Ce reçu fait foi de votre paiement auprès de TontineSN.</p>
            <p style="margin-top:4px;">© {{ date('Y') }} TontineSN — Fait avec ❤️ au Sénégal</p>
        </div>
    </div>

    <div class="no-print" style="text-align:center;margin-top:20px;">
        <button onclick="window.print()" style="background:#009639;color:white;border:none;padding:10px 24px;border-radius:8px;font-size:14px;cursor:pointer;">
            🖨️ Imprimer / Sauvegarder en PDF
        </button>
    </div>
</body>
</html>

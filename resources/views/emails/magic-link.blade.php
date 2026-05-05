<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Inter, Arial, sans-serif; background: #f4f7f6; margin: 0; padding: 40px 0; }
        .card { background: #fff; max-width: 480px; margin: 0 auto; border-radius: 16px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,.08); }
        .logo { font-size: 32px; text-align: center; margin-bottom: 8px; }
        h1 { text-align: center; color: #009639; font-size: 22px; margin: 0 0 8px; }
        p { color: #555; line-height: 1.6; font-size: 15px; }
        .btn { display: block; background: #009639; color: #fff !important; text-decoration: none; text-align: center; padding: 14px 24px; border-radius: 10px; font-size: 16px; font-weight: 600; margin: 28px 0; }
        .note { font-size: 13px; color: #999; text-align: center; }
        .url { word-break: break-all; font-size: 12px; color: #bbb; margin-top: 16px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">🌿</div>
        <h1>TontineSN</h1>
        <p>Bonjour,</p>
        <p>Cliquez sur le bouton ci-dessous pour vous connecter à votre espace TontineSN. Ce lien est valable <strong>15 minutes</strong>.</p>

        <a href="{{ $url }}" class="btn">
            Se connecter à TontineSN
        </a>

        <p class="note">Si vous n'avez pas demandé ce lien, ignorez cet email. Votre compte reste sécurisé.</p>
        <p class="url">{{ $url }}</p>
    </div>
</body>
</html>

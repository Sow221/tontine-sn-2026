<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
body { font-family: Inter, Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
.card { max-width: 520px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
.header { background: #009639; padding: 24px 32px; }
.header h1 { color: white; margin: 0; font-size: 20px; }
.header p { color: rgba(255,255,255,.8); margin: 4px 0 0; font-size: 13px; }
.body { padding: 32px; color: #333; font-size: 15px; line-height: 1.6; }
.footer { padding: 16px 32px; background: #f9f9f9; border-top: 1px solid #eee; font-size: 12px; color: #999; text-align: center; }
</style>
</head>
<body>
<div class="card">
    <div class="header">
        <h1>@yield('title', 'TontineSN')</h1>
        <p>Cotiser simple. Recevoir sûr.</p>
    </div>
    <div class="body">
        @yield('content')
    </div>
    <div class="footer">
        &copy; {{ date('Y') }} TontineSN — Fait avec ❤️ au Sénégal
    </div>
</div>
</body>
</html>

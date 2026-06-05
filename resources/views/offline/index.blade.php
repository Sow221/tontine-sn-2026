<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#009639">
<title>Hors ligne — TontineSN</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  :root {
    --green: #009639; --green-dark: #006B2E;
    --indigo: #2D2F53; --gray: #94A3B8; --bg: #F4F6F9;
  }
  body {
    font-family: -apple-system, 'Inter', sans-serif;
    background: var(--bg); color: var(--indigo);
    min-height: 100vh; display: flex;
    align-items: center; justify-content: center;
    padding: 24px;
  }
  .card {
    background: white; border-radius: 20px;
    padding: 48px 32px; max-width: 400px; width: 100%;
    text-align: center; box-shadow: 0 8px 32px rgba(0,0,0,0.10);
  }
  .icon { font-size: 72px; margin-bottom: 20px; display: block; }
  h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 10px; }
  p { color: var(--gray); font-size: 14px; line-height: 1.6; margin-bottom: 28px; }
  .btn {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--green); color: white;
    border: none; border-radius: 999px;
    padding: 14px 28px; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: background 0.2s, transform 0.1s;
    text-decoration: none;
  }
  .btn:hover { background: var(--green-dark); }
  .btn:active { transform: scale(0.97); }
  .status {
    margin-top: 20px; font-size: 12px; color: var(--gray);
    min-height: 18px;
  }
  .status--online { color: var(--green); font-weight: 600; }
  .logo {
    display: flex; align-items: center; justify-content: center;
    gap: 8px; margin-bottom: 32px;
  }
  .logo-dot {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--green), var(--green-dark));
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 800; font-size: 16px;
  }
  .logo-text { font-weight: 800; font-size: 1.2rem; color: var(--green); }
  .divider { height: 1px; background: #E2E8F0; margin: 24px 0; }
  .tip { font-size: 12px; color: var(--gray); }
  .tip strong { color: var(--indigo); }
</style>
</head>
<body>

<div class="card">
  <div class="logo">
    <div class="logo-dot">T</div>
    <span class="logo-text">TontineSN</span>
  </div>

  <span class="icon" aria-hidden="true">📡</span>
  <h1>Vous êtes hors ligne</h1>
  <p>
    Vérifiez votre connexion internet et réessayez.<br>
    Vos données sont sauvegardées et synchroniseront automatiquement dès le retour du réseau.
  </p>

  <button class="btn" id="retryBtn" onclick="retry()">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
      <polyline points="1 4 1 10 7 10"></polyline>
      <path d="M3.51 15a9 9 0 1 0 .49-3.52"></path>
    </svg>
    Réessayer
  </button>

  <p class="status" id="statusMsg"></p>

  <div class="divider"></div>
  <p class="tip">
    <strong>Astuce :</strong> Installez TontineSN sur votre écran d'accueil pour une meilleure expérience hors ligne.
  </p>
</div>

<script>
function retry() {
  const btn = document.getElementById('retryBtn');
  const msg = document.getElementById('statusMsg');
  btn.disabled = true;
  btn.textContent = 'Vérification…';
  msg.textContent = '';
  msg.className = 'status';

  fetch('/up', { cache: 'no-store' })
    .then(() => {
      msg.textContent = '✓ Connexion rétablie — redirection…';
      msg.className = 'status status--online';
      setTimeout(() => { window.location.href = '/dashboard'; }, 800);
    })
    .catch(() => {
      msg.textContent = 'Toujours hors ligne. Vérifiez votre réseau.';
      btn.disabled = false;
      btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 .49-3.52"></path></svg> Réessayer`;
    });
}

// Auto-retry quand la connexion revient
window.addEventListener('online', () => {
  document.getElementById('statusMsg').textContent = '✓ Connexion détectée…';
  document.getElementById('statusMsg').className = 'status status--online';
  setTimeout(retry, 500);
});

window.addEventListener('offline', () => {
  document.getElementById('statusMsg').textContent = 'Toujours hors ligne.';
  document.getElementById('statusMsg').className = 'status';
});
</script>
</body>
</html>

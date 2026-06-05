Mise en place Open Graph images et deep links

Étapes pour rendre la fonctionnalité OPÉRATIONNELLE :

1) Installer Intervention Image (recommandé) :

```bash
cd tontine-sn
composer require intervention/image
```

2) (Optionnel) installer police Inter dans `public/fonts/Inter-Regular.ttf` ou modifier le code pour une police disponible.

3) Lier le dossier de stockage public :

```bash
php artisan storage:link
```

4) Tester la génération d'OG image :

Ouvrez dans le navigateur : `https://your.localhost/posts/1` et `https://your.localhost/og/posts/1.png`.

5) Production locale gratuite
- `storage/app/public/og` est déjà rendu public via `php artisan storage:link`.
- Les images OG sont servies en local via la route `/og/posts/{post}.png`.
- Si vous souhaitez plus tard ajouter un CDN, ce sera une amélioration optionnelle, mais ce n’est pas nécessaire pour que le partage fonctionne.

6) Deep links (Android/iOS)
- Remplacer les placeholders dans `public/.well-known/assetlinks.json` par le `package_name` et `sha256` de votre clé de signature Android.
- Remplacer `TEAMID.com.example.myapp` dans `public/.well-known/apple-app-site-association` par votre `Team ID` et `Bundle ID`.
- Ajouter `applinks:yourdomain.com` et intent filters dans les apps.

7) Tests
- Facebook Sharing Debugger: https://developers.facebook.com/tools/debug/
- LinkedIn Post Inspector: https://www.linkedin.com/post-inspector/
- WhatsApp: envoyer lien à un contact et vérifier preview

Notes:
- Les bots lisent le HTML initial : assurez-vous que les meta OG sont rendus côté serveur (Blade / SSR). Si votre frontend est SPA, utilisez un prerender service.
- Pour images riches (cover + avatars + badges), je recommande un service Node avec Puppeteer pour screenshots HTML/CSS (meilleure flexibilité). Intervention reste performant pour templates simples.

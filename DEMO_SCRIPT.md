# 🎯 Script de Démonstration — TontineSN
## Jury / Soutenance — Durée totale : 5 à 7 minutes

---

## 🔐 Comptes à utiliser

| Rôle | Email | Mot de passe | Quand l'utiliser |
|------|-------|-------------|-----------------|
| Membre actif | `fatou@tontinesn.test` | `password` | Démo membre (vue normale) |
| Admin | `admin@tontinesn.test` | `password` | Démo admin (vue gestion) |
| Membre avec retard | `pierre@tontinesn.test` | `password` | Montrer les alertes retard |

---

## 📍 SÉQUENCE DE DÉMONSTRATION

---

### ÉTAPE 1 — Connexion membre (30 secondes)

**Ouvrir :** `/login`  
**Se connecter avec :** `fatou@tontinesn.test`

**Ce que le jury voit immédiatement sur le dashboard :**
- Salutation contextuelle "Bonjour, Fatou 👋"
- Hero avec 3 KPIs : nombre de tontines actives, cotisations à payer, série de paiements 🔥
- Les tontines actives avec leur statut (Payé / À payer)
- **Sur les cartes "À payer" → les logos Wave et Orange Money sont visibles sans cliquer**

> 💬 **Phrase à dire :** *"Dès la connexion, le membre voit exactement ce qu'il doit faire et avec quels moyens de paiement."*

---

### ÉTAPE 2 — Démonstration du paiement (90 secondes)

**C'est le moment central. Ne pas le rater.**

**Scénario :** Fatou a une cotisation à payer dans la **Tontine Famille Diallo** (25 000 FCFA).

**Action :** Cliquer sur le bouton **"Payer"** visible sur le dashboard (section "Cotisations à payer")

**Ce que le jury voit sur la page de paiement `/cycles/{id}/pay` :**

1. **Récapitulatif en haut** — montant mis en avant : **25 000 FCFA** avec l'icône pièces
2. **5 méthodes de paiement** visibles en grand :
   - 🔵 **Wave** — logo officiel, fond vert clair, badge "Recommandé", paiement instantané
   - 🟠 **Orange Money** — logo officiel, fond orange clair, paiement mobile
   - 💳 **Carte bancaire** — Visa / Mastercard
   - 🔴 **Free Money** — Free Sénégal
   - 💵 **Espèces** — remise en main propre
3. Sélectionner Wave → la carte se surligne en vert avec une coche ✓
4. Note PayTech visible : *"Traité via PayTech — plateforme sécurisée"*
5. Cliquer **"Payer 25 000 FCFA"** → modale de confirmation s'ouvre
6. La modale affiche : le montant, la méthode choisie, bouton "Confirmer"
7. **Ne pas confirmer** (on ne veut pas vraiment payer en démo)

> 💬 **Phrase à dire :** *"L'intégration Wave et Orange Money est réelle — via PayTech, l'utilisateur est redirigé vers la page de paiement de l'opérateur. Tout est sécurisé TLS 1.3."*

---

### ÉTAPE 3 — Richesse du dashboard membre (60 secondes)

**Rester sur le dashboard de Fatou. Scroller lentement.**

Montrer dans l'ordre :

1. **Section "Mes tontines actives"**
   - Tontine Famille Diallo (fixe mensuelle) — barre de progression du cycle
   - Pot actuel visible : **150 000 FCFA** (8 membres × 25 000 F)
   - Tontine Médina (petite tontine 3 membres)
   - Tontine Dakar Plateau (terminée ✅)

2. **Calendrier des échéances** — bande horizontale avec les dates cliquables

3. **Score crédit** — anneau SVG avec la note `/10`, badge couleur

4. **Série de paiements** 🔥 — streak actuel + record personnel

5. **Graphique 12 mois** — barres des cotisations versées

> 💬 **Phrase à dire :** *"Le membre a une vision complète de sa situation financière dans toutes ses tontines, avec un score de crédit basé sur son historique de paiement."*

---

### ÉTAPE 4 — Vue détail d'une tontine (45 secondes)

**Cliquer sur la Tontine Famille Diallo**

**Ce que le jury voit :**
- Navigation par onglets sticky : Cycle · Stats · Membres · Gestion
- **Pot actuel mis en avant** avec `.pot-highlight`
- Barre de progression du cycle en cours
- Section membres avec les statuts (Payé ✅ / En attente ⏳)
- Bouton de partage WhatsApp avec code d'invitation
- QR Code d'invitation

> 💬 **Phrase à dire :** *"Le créateur gère tout depuis cette page : il voit qui a payé, peut inviter par WhatsApp ou QR Code, et effectuer le tirage quand tous les membres ont cotisé."*

---

### ÉTAPE 5 — Connexion Admin (60 secondes)

**Se déconnecter → Se connecter avec :** `admin@tontinesn.test`

**Dashboard Admin — ce que le jury voit :**

**KPIs en haut :**
- Nombre total d'utilisateurs
- Tontines actives
- FCFA collectés (total en M)
- **KYC en attente → avec bouton "Traiter maintenant →"** (démontrer que c'est actionnable)

**Bloc "Aujourd'hui" :**
- Transactions du jour
- Nouveaux inscrits
- KYC soumis aujourd'hui

**Graphique transactions 6 mois** — barres rouge/rose

**Alertes proactives :**
- Tontines bloquées (cycles en retard > 7 jours) avec bouton "Voir"
- KYC en attente avec bouton "Vérifier" par utilisateur

> 💬 **Phrase à dire :** *"L'administrateur a une vue temps réel. Les alertes sont actionnables directement — un KYC en attente se valide en 1 clic."*

---

### ÉTAPE 6 — Page Transactions Admin (45 secondes)

**Cliquer sur "Transactions" dans le menu gauche**

**Ce que le jury voit :**
- Liste avec pour chaque transaction :
  - Nom de l'utilisateur + téléphone
  - **Pastille colorée** de l'opérateur (vert Wave, orange Orange Money, rouge Free Money)
  - Nom de la tontine · Date/heure exacte
  - ID transaction en monospace
  - Montant avec **`+`** en vert pour les réussies
  - Bouton "Confirmer" pour les transactions en attente

**Démontrer les filtres :**
- Taper un nom dans la recherche → la liste se filtre
- Sélectionner "Wave" dans le filtre moyen → seulement les Wave

> 💬 **Phrase à dire :** *"L'admin voit chaque transaction avec son opérateur, peut rechercher par nom, filtrer par date, par méthode. Tout est exportable en CSV."*

---

## 🎁 BONUS — Si le jury pose des questions

### "Comment un membre rejoint une tontine ?"
→ Montrer `/tontines/explore` — liste publique avec code d'invitation
→ Ou lien WhatsApp direct depuis le détail d'une tontine

### "C'est quoi le score crédit ?"
→ Sur le dashboard membre, ouvrir le panneau Gamification
→ Score sur 10 basé sur : régularité des paiements, streaks, badges
→ Visible par les autres membres dans le leaderboard

### "La sécurité ?"
→ Montrer la page de paiement → badge "Paiement sécurisé · TLS 1.3"
→ Mentionner : KYC obligatoire pour les gros montants, journaux d'audit admin

### "Les types de tontines ?"
→ **Fixe** (Famille Diallo) — tour séquentiel classique
→ **Enchères** (Enchères Liberté) — on enchérit pour recevoir en priorité
→ **Épargne forcée** (Épargne HLM) — chacun récupère sa propre mise
→ **Cérémoniale** (Mariage Aminata) — cagnotte pour un événement

### "Et sur mobile ?"
→ Rétrécir la fenêtre du navigateur ou montrer sur téléphone
→ Bottom navigation visible, FAB de paiement flottant en bas à droite

---

## ⚠️ PIÈGES À ÉVITER

| Piège | Solution |
|-------|----------|
| Ne pas confirmer le paiement Wave en démo (ça redirige vers PayTech) | Fermer la modale après l'avoir ouverte |
| Tontine Suspendue (SUS001) — ne pas l'ouvrir, rien d'intéressant | Rester sur Famille Diallo ou Enchères Liberté |
| Tontine Dakar Plateau est "terminée" — les boutons de paiement n'apparaissent pas | Utiliser Famille Diallo pour le paiement |
| Pierre n'a pas payé son dernier cycle — intéressant pour montrer les alertes de retard | Utile si le jury pose la question |

---

## 🗺️ ORDRE DES PAGES (copier-coller dans le navigateur)

```
1. /login                          → connexion fatou
2. /dashboard                      → vue membre
3. /cycles/{id}/pay                → page de paiement (via bouton "Payer" du dashboard)
4. /tontines (chercher FAM001)     → détail tontine Famille Diallo
5. Déconnexion → /login            → connexion admin
6. /admin/dashboard                → vue admin
7. /admin/transactions             → liste transactions avec filtres
```

---

## ✅ CHECKLIST AVANT LA DÉMO

- [ ] Données demo seedées : `php artisan db:seed --class=DemoDataSeeder`
- [ ] App lancée et accessible
- [ ] Onglet navigateur en plein écran (F11)
- [ ] Zoom navigateur à 100% (pas 90% ni 110%)
- [ ] Connexion testée avec `fatou@tontinesn.test` / `password`
- [ ] Connexion testée avec `admin@tontinesn.test` / `password`
- [ ] Images des logos opérateurs présentes dans `public/images/` (logo wave.png, logo orange money.png)
- [ ] Mode clair actif (plus lisible pour le jury en salle)

---

**Durée totale estimée : 5 min 30 sec**  
**Étapes 1-2-5-6 sont obligatoires. Étapes 3-4 si le temps le permet.**

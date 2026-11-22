# eTrésorerie Executive V2 — Capacitor

## Installation serveur (obligatoire)

Copier UNIQUEMENT `SERVEUR_PHP/api/executive/dashboard_complet.php` dans :
`C:\xampp\htdocs\congo-suite\api\executive\dashboard_complet.php`

Ne remplacez PAS `bootstrap.php`, `secret.php`, `health.php`, ni la connexion MySQL. Ce nouvel endpoint utilise les contrôles d’authentification déjà installés. Il est en lecture seule.

## Compilation APK

Décompresser le projet, puis envoyer **les fichiers contenus dans ce dossier** à la racine du dépôt GitHub existant. Remplacer `src/main.js` et `src/style.css` et ajouter les autres fichiers. Le workflow `.github/workflows/android-debug-apk.yml` génère l’APK dans Actions > Artifacts.

## Connexion

Dans l’APK, renseigner la racine HTTPS `https://<tunnel>.trycloudflare.com/congo-suite/` et la clé API déjà définie côté serveur. Le tunnel doit rester actif.

## Couverture

- Vue générale : trésorerie, entrées, décaissements, situation par site, évolution 6 mois, dépenses par site, approvisionnements directs.
- Fonds injectés : synthèse des encaissements et approvisionnements directs.
- Caisses : totaux caisses et banques + situation par site.
- Dépenses : synthèse et liste si l’endpoint historique répond.
- Demandes : onglet préparé, détails non encore connectés (API spécifique requise).

**Validation nécessaire** : comparer les montants de l’APK et du Dashboard PHP avec les mêmes dates avant tout usage décisionnel. La clé API partagée antérieurement doit être renouvelée avant la production. Ne publiez pas de secrets dans GitHub.

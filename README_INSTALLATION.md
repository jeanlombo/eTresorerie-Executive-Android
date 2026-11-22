# eTrésorerie Executive — Android Capacitor

## Projet
Interface mobile en lecture seule, basée sur Capacitor 8 / Vite 7, dérivée de la structure du projet Android AFRICA CONSULTING GROUPE SARL.

## API existante
Le projet serveur fourni comporte déjà `api/executive/health.php`, `dashboard.php`, `sites.php` et `decaissements.php` ainsi qu'une authentification par `X-API-Key`.

**Ne pas envoyer la clé API dans l’URL.** Elle est saisie dans l’application et conservée seulement en mémoire pendant son utilisation.

## Préparer le serveur
Le serveur doit autoriser explicitement l'origine Android Capacitor `https://localhost` (et `http://localhost` si nécessaire) pour les requêtes CORS et `X-API-Key`. Voir `PATCH_CORS_EXEMPLE.txt`. Ne pas ouvrir CORS à toutes les origines en production.

L'URL à saisir doit être la **racine du système** (pas `/executive/index.php`, pas `/api/executive/`). Exemple : `https://xxxx.trycloudflare.com/etresorerie/`.

## Compiler via GitHub
1. Créer un dépôt GitHub **privé** vide.
2. Déposer le **contenu** de ce dossier à la racine du dépôt (y compris `.github/workflows`).
3. Ouvrir **Actions** → **eTresorerie Executive - APK Android** → **Run workflow**.
4. Une fois le build réussi, télécharger l'artifact `eTresorerie-Executive-debug-APK` et extraire `app-debug.apk`.
5. Installer sur un appareil Android autorisé aux tests.

## En local
`npm install`, puis `npm run dev` pour prévisualiser. `npm run build` pour vérifier Vite. Pour Android Studio : `npx cap add android`, `npx cap sync android`, `npx cap open android`.

## Limites
Cette première version consulte les API Executive existantes, elle n'est pas encore une reprise exhaustive des 9 pages PHP. Les rubriques apports, encaissements et demandes détaillées nécessitent des endpoints dédiés et des contrôles d'autorisation par utilisateur. Les calculs de l'ancienne API peuvent différer des soldes multicaisses actuels : rapprochement obligatoire avant validation.

# 🚀 Guide de déploiement Sinphony sur Vercel + PWA

## Vue d'ensemble

Sinphony est maintenant configurée en tant que **Progressive Web App (PWA)** avec support offline complet. Elle peut être déployée sur Vercel et installée directement sur votre téléphone.

## Architecture

```
┌─────────────────────┐
│   Vercel (Frontend) │  ← Frontend statique + PWA + Service Worker
│   - React / Static  │     Accessible sur https://votre-app.vercel.app
└────────────┬────────┘
             │ API Calls
             ▼
┌─────────────────────┐
│  Backend Symfony    │  ← API REST backend (auto-hébergé ou Railway/Render)
│  - Routes API       │     Écoute sur https://api.votre-domaine.com
│  - Base de données  │
└─────────────────────┘
```

## ✅ Étape 1 : Préparation locale

Avant de déployer, teste en local que tout fonctionne :

```bash
# Démarrer ton app Symfony
php bin/console server:run

# Visite http://localhost:8000 pour vérifier
# - La PWA se charge correctement
# - Le service worker s'enregistre (console du navigateur)
# - L'app fonctionne en offline (devtools → Network → Offline)
```

### Vérifier le service worker

Dans le navigateur (F12), onglet **Application**:
- ✅ **Manifest** doit être listé
- ✅ **Service Worker** doit être "running"
- ✅ **Cache** doit contenir les assets et musiques

## 🌐 Étape 2 : Déployer le Backend

Puisque Vercel ne supporte pas PHP nativement, tu dois héberger Symfony ailleurs.

### Options (classées par facilité):

#### A) **Railway** (⭐ Recommandé - Gratuit les 5$ par mois)
```bash
npm install -g railway

# Se connecter à Railway
railway login

# À la racine du projet
railway init

# Suivre les instructions et déployer
railway up
```

#### B) **Render** (Gratuit)
- Va sur https://render.com
- Crée un nouveau "Web Service"
- Connecte ton repo GitHub
- Configure les variables d'environnement
- Déploie

#### C) **Heroku** (Payant maintenant, ~$7/mois)
```bash
heroku login
heroku create votre-app-name
git push heroku main
```

### Variables d'environnement backend
Tu vas avoir besoin de :
```
DATABASE_URL=postgresql://...
APP_ENV=prod
APP_SECRET=votre_clé_secrète
```

### Récupérer l'URL du backend
Après déploiement, tu auras une URL comme :
```
https://sinphony-api.railway.app
ou
https://sinphony-api.onrender.com
```

## 🎀 Étape 3 : Déployer le Frontend sur Vercel

### Cloner et configurer

```bash
# Si tu n'as pas encore git init
git init
git add .
git commit -m "Initial commit"

# Créer un repo GitHub (optionnel mais recommandé)
# Puis pusher
git push origin main
```

### Sur Vercel

#### Option A: Via Git (recommandé)
1. Va sur https://vercel.com
2. Clique "New Project"
3. Importe ton repo GitHub
4. Vercel détecte automatiquement la config
5. Ajoute une variable d'env:
   - **REACT_APP_API_URL** = `https://ton-backend.railway.app`
   - **NEXT_PUBLIC_API_URL** = `https://ton-backend.railway.app` (si Next.js)
6. Clique "Deploy"

#### Option B: Via CLI
```bash
npm install -g vercel
vercel login
vercel
# Réponds aux questions et suit les instructions
```

### Configurer l'API URL au frontend

Tu dois mettre à jour l'URL de l'API dans ton code. Dans `public/js/player.js`, cherche tous les appels `fetch` et remplace les URLs locales par :

```javascript
const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';

// Exemple:
fetch(`${API_URL}/youtube/download`, {
  method: 'POST',
  ...
})
```

## 📱 Étape 4 : Installer sur le téléphone

### iPhone
1. Ouvre ton app dans Safari
2. Clique le bouton "Partage" (carré avec flèche)
3. Sélectionne "Sur l'écran d'accueil"
4. Nomme-la "Sinphony"
5. C'est installé! 🎉

### Android
1. Ouvre ton app dans Chrome
2. Clique le menu (3 points)
3. Sélectionne "Installer l'app"
4. Confirme
5. L'app apparaît sur l'écran d'accueil 🎉

## 🔒 Support Offline

Une fois installée, ton app:
- ✅ Fonctionne **sans Internet** (si tu avais déjà écouté les musiques)
- ✅ **Cache les musiques** automatiquement au 1er accès
- ✅ **Sync les données** quand tu reviens online
- ✅ **Permet l'ajout de musiques** hors ligne (synced au retour)

### Tester l'offline
1. Sur le téléphone: actifs le mode avion
2. Ouvre l'app
3. Les musiques mises en cache se lisent normalement
4. Les musiques non cachées affichent un message d'erreur
5. Désactifs le mode avion
6. L'app va essayer de recharger et synchroniser

## 📊 Monitoring

### Vercel Dashboard
https://vercel.com/dashboard → Vois les déploiements et les erreurs

### Logs du backend
```bash
# Pour Railway
railway logs

# Pour Render (depuis le dashboard)

# Pour Heroku
heroku logs --tail
```

## 🛠️ Variables d'environnement

**Frontend (Vercel):**
```
REACT_APP_API_URL=https://sinphony-api.railway.app
REACT_APP_ENVIRONMENT=production
```

**Backend (Railway/Render/Heroku):**
```
DATABASE_URL=postgresql://...
APP_ENV=prod
APP_SECRET=xxxxx
CORS_ALLOW_ORIGIN=https://sinphony.vercel.app
```

## 🐛 Troubleshooting

### "Service Worker ne s'enregistre pas"
```javascript
// Ajoute ceci dans la console du navigateur
navigator.serviceWorker.getRegistrations().then(registrations => {
  registrations.forEach(registration => registration.unregister());
});
// Puis rafraîchis la page
```

### "API retourne une erreur CORS"
Assure-toi que ton backend Symfony a ces headers:
```php
header('Access-Control-Allow-Origin: https://sinphony.vercel.app');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

### "Les musiques ne se cachent pas"
Vérifie que:
- Le service worker est enregistré (Application tab → Service Workers)
- Les fichiers audio sont téléchargés (Application tab → Cache Storage)
- Pas d'erreur 404 dans la console

## 📚 Ressources

- [Vercel Docs](https://vercel.com/docs)
- [PWA Checklist](https://web.dev/pwa-checklist/)
- [Service Workers](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Web App Manifest](https://developer.mozilla.org/en-US/docs/Web/Manifest)

---

**Besoin d'aide?** Les fichiers clés:
- `vercel.json` - Configuration Vercel
- `public/manifest.json` - Configuration PWA
- `public/service-worker.js` - Cache et offline
- `public/css/style.css` - Responsive design

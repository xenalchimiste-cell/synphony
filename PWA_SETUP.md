# 📱 Setup PWA + Responsive - Guide Rapide

## ✅ Ce qui a été fait

### 1. **Progressive Web App (PWA)**
✅ `public/manifest.json` - Configuration PWA officielle
✅ `public/service-worker.js` - Gestionnaire de cache offline
✅ Enregistrement du service worker dans `index.html`
✅ Meta tags pour mobile (Apple, Android)

### 2. **Responsive Design**
✅ Media queries ajoutées au `public/css/style.css`:
   - Tablets (1024px)
   - Mobile (768px)
   - Small phones (480px)
   - Landscape mode
   - Safe area support (iPhone X+)

### 3. **Configuration Vercel**
✅ `vercel.json` - Configuration de déploiement
✅ `package.json` - Build configuration
✅ Support des rewrites API

### 4. **Documentation**
✅ `DEPLOYMENT.md` - Guide complet de déploiement

---

## 🚀 Démarrage rapide

### 1️⃣ Tester en local

```bash
# Démarrer Symfony
php bin/console server:run

# Ouvrir http://localhost:8000
# Tester sur mobile avec Dev Tools (F12 → Toggle device toolbar)
```

### 2️⃣ Tester le service worker

Dans la console du navigateur (F12):

```javascript
// Voir si le service worker est enregistré
navigator.serviceWorker.getRegistrations().then(regs => {
  console.log('Service Workers:', regs);
  regs.forEach(reg => console.log('✓', reg.scope));
});

// Tester le cache
caches.keys().then(names => {
  console.log('Caches:', names);
});
```

### 3️⃣ Tester l'offline

1. F12 → Network
2. Cocher "Offline"
3. Actualiser la page
4. L'app doit rester fonctionnelle (au moins les pages cachées)

### 4️⃣ Préparer le déploiement

```bash
# Initialiser git (si pas déjà fait)
git init
git add .
git commit -m "feat: PWA setup with offline support and responsive design"

# Créer un repo GitHub vide
# Puis:
git remote add origin https://github.com/tonusername/sinphony.git
git branch -M main
git push -u origin main
```

---

## 🌐 Étapes de déploiement

### Étape 1: Déployer le Backend (Railway)

```bash
# Installer Railway CLI
npm install -g railway

# Se connecter
railway login

# Initialiser et déployer
railway init
# Sélectionne "Symfony" ou "PHP"
# Suis les instructions

# Récupérer l'URL
railway open
# Tu verras quelque chose comme: https://sinphony-api-prod.up.railway.app
```

### Étape 2: Déployer sur Vercel

1. Va sur https://vercel.com
2. Clique "New Project"
3. Sélectionne ton repo GitHub
4. Vercel détecte automatiquement les configs
5. Ajoute variable d'environnement:
   ```
   REACT_APP_API_URL=https://sinphony-api-prod.up.railway.app
   ```
6. Clique "Deploy"

### Étape 3: Installer sur le téléphone

#### iPhone (Safari)
1. Ouvre https://sinphony.vercel.app
2. Clique "Partage" → "Sur l'écran d'accueil"
3. Accepte
4. L'app s'ajoute à l'écran d'accueil

#### Android (Chrome)
1. Ouvre https://sinphony.vercel.app
2. Menu (⋮) → "Installer l'app"
3. Accepte
4. L'app s'ajoute à l'écran d'accueil

---

## 📊 Architecture finale

```
📦 Sinphony
│
├─ 🖥️ Frontend (Vercel)
│  ├─ public/
│  │  ├─ manifest.json (configuration PWA)
│  │  ├─ service-worker.js (cache offline)
│  │  ├─ css/style.css (responsive)
│  │  └─ js/player.js
│  ├─ templates/
│  │  └─ music/index.html.twig
│  └─ vercel.json
│
└─ 🔌 Backend (Railway)
   ├─ src/Controller/MusicController.php
   ├─ src/Service/YouTubeDownloader.php
   ├─ public/music/uploads/
   └─ config/

Key Features:
✅ PWA - Installable sur mobile
✅ Offline - Fonctionne sans internet
✅ Responsive - Adapté à tous les écrans
✅ Cache Smart - Musiques cachées automatiquement
✅ Sync - Synchronise au retour online
```

---

## 🎯 Points clés

| Feature | Status | Où? |
|---------|--------|-----|
| PWA Installation | ✅ | manifest.json |
| Offline Support | ✅ | service-worker.js |
| Responsive (Mobile) | ✅ | style.css (media queries) |
| Responsive (Tablet) | ✅ | style.css (media queries) |
| Responsive (Desktop) | ✅ | style.css (existing) |
| Audio Caching | ✅ | service-worker.js |
| API Calls | ✅ | player.js + vercel.json rewrites |
| Vercel Deploy | ✅ | vercel.json |

---

## ⚙️ Configuration des APIs

Pour que les appels API fonctionnent en prod, assure-toi que:

1. **Backend a les CORS configurés:**
   ```php
   // Dans ton contrôleur ou middleware
   header('Access-Control-Allow-Origin: https://sinphony.vercel.app');
   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
   header('Access-Control-Allow-Headers: Content-Type');
   ```

2. **Frontend utilise l'URL d'env:**
   ```javascript
   const API_URL = process.env.REACT_APP_API_URL || 'http://localhost:8000';
   ```

3. **Vercel a la variable d'env:**
   ```
   Dashboard → Settings → Environment Variables
   REACT_APP_API_URL=https://ton-api.railway.app
   ```

---

## 🧪 Checklist finale

- [ ] Service worker s'enregistre (F12 → Application → Service Workers)
- [ ] Manifest est valide (F12 → Application → Manifest)
- [ ] App offline fonctionne (Network → Offline)
- [ ] Responsive sur mobile (F12 → Toggle device toolbar)
- [ ] Git repo créé et pushé
- [ ] Backend déployé (Railway)
- [ ] Frontend déployé (Vercel)
- [ ] App installée sur téléphone
- [ ] Offline fonctionne sur téléphone (Mode avion)

---

## 📞 Support

Si tu as besoin d'aide:
1. Consulte `DEPLOYMENT.md` pour les détails complets
2. Vérifie la console (F12) pour les erreurs
3. Utilise les logs: `railway logs` ou Vercel dashboard

Bonne chance! 🚀

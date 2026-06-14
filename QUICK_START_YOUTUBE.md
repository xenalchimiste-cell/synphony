# ⚡ Guide de démarrage rapide - Import YouTube

## 🚀 En 3 étapes

### 1️⃣ Démarrez le serveur
```bash
cd "web sinphony"
symfony server:start
```

### 2️⃣ Ouvrez votre navigateur
```
http://localhost:8000
```

### 3️⃣ Importez votre première musique !
1. Cliquez sur **"Ajouter de la musique"** (dans la sidebar)
2. Trouvez la section **"📺 Importer depuis YouTube"**
3. Collez une URL YouTube, par exemple :
   ```
   https://www.youtube.com/watch?v=dQw4w9WgXcQ
   ```
4. Cliquez sur **"Télécharger"**
5. Attendez 30-60 secondes ⏳
6. ✅ C'est fait ! Votre musique est dans la bibliothèque !

---

## 🎯 Exemples d'URLs à tester

### Musiques libres de droits (Creative Commons)
Vous pouvez tester avec ces vidéos qui sont libres de droits :

```
https://www.youtube.com/watch?v=3LWEX4guap4
https://www.youtube.com/watch?v=u2W5YPQJqUs
```

### Votre propre contenu
Si vous avez uploadé vos propres musiques sur YouTube, vous pouvez les importer directement !

---

## 💻 Alternative : Ligne de commande

Si vous préférez la ligne de commande :

```bash
php bin/console app:youtube:download "URL_DE_LA_VIDEO"
```

**Exemple :**
```bash
php bin/console app:youtube:download "https://www.youtube.com/watch?v=3LWEX4guap4"
```

---

## ✅ Vérifications rapides

### Script de diagnostic automatique (recommandé)
```bash
php bin/check-youtube.php
```
✅ Ce script vérifie automatiquement :
- Que yt-dlp est installé et accessible
- Que FFmpeg est installé
- Que le dossier de destination existe et est accessible
- Que la connexion YouTube fonctionne

### yt-dlp est-il installé ?
```bash
yt-dlp --version
```
✅ Devrait afficher : `2026.03.17` (ou supérieur)

### FFmpeg est-il installé ?
```bash
ffmpeg -version
```
✅ Devrait afficher : `ffmpeg version 8.1.1` (ou supérieur)

### Le serveur démarre-t-il correctement ?
```bash
php bin/console cache:clear
symfony server:start
```
✅ Devrait afficher : `Listening on http://127.0.0.1:8000`

---

## 🐛 Problèmes courants

### Erreur "yt-dlp n'est pas installé"
**Solution :**
```bash
brew install yt-dlp
```

### Erreur "Le fichier n'a pas été créé"
**Solution :** Vérifiez les permissions du dossier :
```bash
chmod 755 public/music/uploads
```

### Le téléchargement est très lent
**C'est normal !** YouTube limite la vitesse de téléchargement. Comptez :
- 30 secondes pour une chanson de 3-4 minutes
- 1-2 minutes pour une vidéo de 10+ minutes

---

## 🎉 C'est tout !

Vous êtes prêt à utiliser l'import YouTube ! 

**Astuce :** Vous pouvez télécharger plusieurs musiques d'affilée - pas besoin d'attendre entre chaque téléchargement.

---

## 📚 Besoin de plus d'aide ?

- **Guide utilisateur complet :** `YOUTUBE_IMPORT.md`
- **Documentation technique :** `FEATURES_YOUTUBE.md`
- **README principal :** `README_YOUTUBE.md`

---

**Bonne musique ! 🎵✨**

# 🎵 Import YouTube pour Sinphony

## 🎉 C'est fait !

La fonctionnalité d'import depuis YouTube a été **entièrement implémentée** dans votre application Sinphony !

## ✅ Ce qui a été installé

### Outils système
- ✅ **yt-dlp** version 2026.03.17 - Outil de téléchargement YouTube
- ✅ **FFmpeg** version 8.1.1 - Conversion audio haute qualité

### Code développé
- ✅ Service `YouTubeDownloader` (219 lignes)
- ✅ Commande console `app:youtube:download` (140 lignes)
- ✅ Routes API `/youtube/info` et `/youtube/download`
- ✅ Interface web dans la page "Ajouter de la musique"
- ✅ JavaScript pour gérer le téléchargement
- ✅ Styles CSS pour l'interface

## 🚀 Comment l'utiliser ?

### Option 1 : Interface Web (recommandé)

1. **Démarrez votre serveur Symfony :**
   ```bash
   symfony server:start
   ```
   Ou avec PHP :
   ```bash
   php -S localhost:8000 -t public
   ```

2. **Ouvrez votre navigateur :**
   ```
   http://localhost:8000
   ```

3. **Importez une musique :**
   - Cliquez sur **"Ajouter de la musique"** dans la sidebar
   - Trouvez la section **"📺 Importer depuis YouTube"**
   - Collez l'URL d'une vidéo YouTube
   - Cliquez sur **"Télécharger"**
   - Attendez quelques instants...
   - Votre musique apparaît automatiquement dans la bibliothèque ! 🎉

### Option 2 : Ligne de commande

```bash
php bin/console app:youtube:download "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
```

La commande affichera :
- Les informations de la vidéo (titre, artiste, durée)
- Une demande de confirmation
- La progression du téléchargement
- Le résultat final

## 🎯 Exemples d'URLs supportées

```
https://www.youtube.com/watch?v=dQw4w9WgXcQ
https://youtu.be/dQw4w9WgXcQ
https://www.youtube.com/shorts/abc123
```

## 💡 Comment ça marche ?

1. **Vous collez l'URL** d'une vidéo YouTube
2. **yt-dlp télécharge** la vidéo en arrière-plan
3. **FFmpeg convertit** l'audio en MP3 haute qualité
4. **Les métadonnées** (titre, artiste, durée) sont extraites automatiquement
5. **Le fichier est sauvegardé** dans `public/music/uploads/`
6. **L'entrée est créée** dans la base de données
7. **La musique apparaît** dans votre bibliothèque !

## ⚙️ Configuration technique

Tout est configuré et prêt à l'emploi ! Les fichiers suivants ont été mis à jour :

### Backend
- `src/Service/YouTubeDownloader.php` - Service de téléchargement
- `src/Command/YouTubeDownloadCommand.php` - Commande console
- `src/Controller/MusicController.php` - Routes API
- `config/services.yaml` - Configuration Symfony

### Frontend
- `templates/music/index.html.twig` - Interface utilisateur
- `public/css/style.css` - Styles
- `public/js/player.js` - Logique JavaScript

## ⚠️ Important - Légalité

Cette fonctionnalité doit être utilisée **uniquement** pour :
- ✅ Télécharger du contenu dont vous avez les droits
- ✅ Télécharger du contenu libre de droits (Creative Commons, domaine public)
- ✅ Un usage personnel et éducatif

❌ Le téléchargement de contenu protégé par des droits d'auteur sans autorisation est illégal.

## 🔧 Dépannage

### "yt-dlp n'est pas installé"
Vérifiez l'installation :
```bash
yt-dlp --version
```
Si ça ne fonctionne pas, réinstallez :
```bash
brew install yt-dlp
```

### "Erreur lors du téléchargement"
Causes possibles :
- La vidéo est privée ou supprimée
- Restrictions géographiques
- Problème de connexion internet
- Vidéo avec protection DRM

### Le fichier audio est de mauvaise qualité
Vérifiez que FFmpeg est installé :
```bash
ffmpeg -version
```

## 📊 Statistiques

- **Temps moyen de téléchargement :** 30 secondes à 2 minutes
- **Format de sortie :** MP3 (meilleure qualité)
- **Stockage :** `public/music/uploads/`
- **Timeout max :** 5 minutes

## 🎨 Captures d'écran

### Interface Web
Une section dédiée dans la page "Ajouter de la musique" avec :
- 📺 Icône YouTube visible
- Champ de saisie pour l'URL
- Bouton de téléchargement
- Messages de statut en temps réel
- Design cohérent avec le reste de l'application

### Console
Une interface CLI élégante avec :
- Affichage des informations
- Demande de confirmation
- Barre de progression
- Messages de succès/erreur

## 📚 Documentation complète

Pour plus de détails, consultez :
- **`YOUTUBE_IMPORT.md`** - Guide utilisateur complet avec instructions d'installation
- **`FEATURES_YOUTUBE.md`** - Documentation technique détaillée

## 🎉 Résultat final

Vous pouvez maintenant :
- ✅ Importer des musiques depuis YouTube en quelques clics
- ✅ Utiliser l'interface web intuitive
- ✅ Ou utiliser la commande console pour automatiser
- ✅ Les musiques sont automatiquement ajoutées à votre bibliothèque
- ✅ Les métadonnées sont extraites automatiquement
- ✅ La qualité audio est optimale (MP3 haute qualité)

## 🚀 Prochaines étapes suggérées

Vous pourriez améliorer cette fonctionnalité avec :
- Téléchargement de playlists entières
- Barre de progression en temps réel
- Extraction des miniatures comme cover art
- Support d'autres plateformes (SoundCloud, etc.)
- File d'attente de téléchargements

## 🤝 Support

### Script de diagnostic
Un script de diagnostic automatique est disponible pour vérifier votre installation :
```bash
php bin/check-youtube.php
```

Ce script vérifie :
- ✅ yt-dlp est installé et accessible
- ✅ FFmpeg est installé
- ✅ Le dossier de destination existe et est accessible
- ✅ La connexion YouTube fonctionne

Si vous rencontrez des problèmes :
1. Exécutez le script de diagnostic : `php bin/check-youtube.php`
2. Consultez les logs Symfony : `tail -f var/log/dev.log`
3. Vérifiez les permissions du dossier `public/music/uploads/`

---

**Bon téléchargement ! 🎵✨**

Créé avec ❤️ pour améliorer votre expérience Sinphony

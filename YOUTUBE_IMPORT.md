# 📺 Import depuis YouTube - Guide d'installation

Ce guide vous explique comment activer l'import de musiques depuis YouTube dans Sinphony.

## 🔧 Prérequis

### Installation de yt-dlp

**yt-dlp** est l'outil qui permet de télécharger les vidéos YouTube. Voici comment l'installer selon votre système d'exploitation :

#### macOS (Homebrew)
```bash
brew install yt-dlp
```

#### macOS (pip - alternative)
```bash
pip3 install yt-dlp
```

#### Linux (Ubuntu/Debian)
```bash
sudo apt update
sudo apt install yt-dlp
```

Ou via pip :
```bash
pip3 install yt-dlp
```

#### Vérification de l'installation
```bash
yt-dlp --version
```

Si la commande affiche un numéro de version, c'est bon ! ✅

### Installation de FFmpeg (optionnel mais recommandé)

FFmpeg est nécessaire pour la conversion audio en MP3 de haute qualité.

#### macOS
```bash
brew install ffmpeg
```

#### Linux (Ubuntu/Debian)
```bash
sudo apt install ffmpeg
```

#### Vérification
```bash
ffmpeg -version
```

## 🎵 Utilisation

### Via l'interface web

1. Lancez votre serveur Symfony :
   ```bash
   symfony server:start
   ```

2. Ouvrez votre navigateur sur `http://localhost:8000`

3. Cliquez sur **"Ajouter de la musique"** dans la sidebar

4. Dans la section **"📺 Importer depuis YouTube"** :
   - Collez l'URL d'une vidéo YouTube
   - Cliquez sur **"Télécharger"**
   - Attendez que le téléchargement se termine
   - La musique est automatiquement ajoutée à votre bibliothèque ! 🎉

### Via la ligne de commande

Vous pouvez également télécharger des musiques via le terminal :

```bash
php bin/console app:youtube:download "https://www.youtube.com/watch?v=VIDEO_ID"
```

La commande vous affichera :
- Les informations de la vidéo (titre, artiste, durée)
- Une confirmation avant téléchargement
- La progression du téléchargement
- Le résultat final

#### Exemple
```bash
php bin/console app:youtube:download "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
```

## 🎯 Formats d'URL supportés

L'import fonctionne avec tous les formats d'URL YouTube :

- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/shorts/VIDEO_ID`

## 🔍 Que se passe-t-il lors de l'import ?

1. **Téléchargement** : La vidéo est téléchargée depuis YouTube
2. **Conversion** : L'audio est extrait et converti en MP3 (meilleure qualité)
3. **Métadonnées** : Le titre, l'artiste et la durée sont automatiquement extraits
4. **Stockage** : Le fichier est sauvegardé dans `public/music/uploads/`
5. **Base de données** : Les informations sont enregistrées dans votre bibliothèque

## ⚠️ Notes importantes

### Légalité et droits d'auteur

⚠️ **IMPORTANT** : Cette fonctionnalité doit être utilisée **uniquement** pour :
- Télécharger du contenu dont vous avez les droits
- Télécharger du contenu libre de droits
- Un usage personnel et éducatif

Le téléchargement de contenu protégé par des droits d'auteur sans autorisation est illégal dans de nombreux pays.

### Performance

- Le téléchargement peut prendre de **30 secondes à plusieurs minutes** selon :
  - La durée de la vidéo
  - La qualité audio
  - Votre connexion internet

- Pour les vidéos longues (>10 minutes), privilégiez la commande console pour voir la progression en temps réel

### Dépannage

#### "yt-dlp n'est pas installé"
→ Assurez-vous d'avoir installé yt-dlp (voir section Prérequis)
→ Vérifiez que la commande `yt-dlp --version` fonctionne

#### "URL YouTube invalide"
→ Vérifiez que l'URL est complète et correcte
→ Essayez de copier l'URL directement depuis la barre d'adresse de YouTube

#### "Erreur lors du téléchargement"
→ Vérifiez votre connexion internet
→ La vidéo peut être privée ou supprimée
→ Certaines vidéos peuvent avoir des restrictions géographiques

#### Le fichier MP3 est de mauvaise qualité
→ Installez FFmpeg pour une meilleure qualité audio
→ yt-dlp utilisera automatiquement FFmpeg s'il est disponible

## 🚀 Fonctionnalités

- ✅ Téléchargement direct depuis YouTube
- ✅ Conversion automatique en MP3
- ✅ Extraction des métadonnées (titre, artiste, durée)
- ✅ Intégration immédiate dans la bibliothèque
- ✅ Interface web et ligne de commande
- ✅ Support de tous les formats d'URL YouTube
- ✅ Feedback en temps réel sur l'état du téléchargement

## 📚 Ressources

- [Documentation yt-dlp](https://github.com/yt-dlp/yt-dlp)
- [Documentation FFmpeg](https://ffmpeg.org/documentation.html)

## 🆘 Support

Si vous rencontrez des problèmes :

1. Vérifiez que yt-dlp est bien installé : `yt-dlp --version`
2. Vérifiez que FFmpeg est installé : `ffmpeg -version`
3. Vérifiez les permissions du dossier `public/music/uploads/`
4. Consultez les logs Symfony : `tail -f var/log/dev.log`

---

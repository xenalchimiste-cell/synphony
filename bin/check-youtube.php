#!/usr/bin/env php
<?php

/**
 * Script de diagnostic pour l'import YouTube
 * Vérifie que tous les outils nécessaires sont installés
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Process\Process;

echo "\n";
echo "🔍 Diagnostic de l'import YouTube pour Sinphony\n";
echo "================================================\n\n";

$allGood = true;

// 1. Vérifier yt-dlp
echo "1️⃣  Vérification de yt-dlp...\n";
$possiblePaths = [
    '/opt/homebrew/bin/yt-dlp',
    '/usr/local/bin/yt-dlp',
    'yt-dlp',
];

$ytDlpFound = false;
$ytDlpPath = null;
$ytDlpVersion = null;

foreach ($possiblePaths as $path) {
    $process = new Process([$path, '--version']);
    $process->run();

    if ($process->isSuccessful()) {
        $ytDlpFound = true;
        $ytDlpPath = $path;
        $ytDlpVersion = trim($process->getOutput());
        break;
    }
}

if ($ytDlpFound) {
    echo "   ✅ yt-dlp trouvé !\n";
    echo "   📍 Chemin : $ytDlpPath\n";
    echo "   📦 Version : $ytDlpVersion\n\n";
} else {
    echo "   ❌ yt-dlp n'est pas installé\n";
    echo "   💡 Installation : brew install yt-dlp\n\n";
    $allGood = false;
}

// 2. Vérifier FFmpeg
echo "2️⃣  Vérification de FFmpeg...\n";
$ffmpegPaths = [
    '/opt/homebrew/bin/ffmpeg',
    '/usr/local/bin/ffmpeg',
    'ffmpeg',
];

$ffmpegFound = false;
$ffmpegPath = null;

foreach ($ffmpegPaths as $path) {
    $process = new Process([$path, '-version']);
    $process->run();

    if ($process->isSuccessful()) {
        $ffmpegFound = true;
        $ffmpegPath = $path;
        $output = $process->getOutput();
        preg_match('/ffmpeg version (\S+)/', $output, $matches);
        $ffmpegVersion = $matches[1] ?? 'inconnu';
        break;
    }
}

if ($ffmpegFound) {
    echo "   ✅ FFmpeg trouvé !\n";
    echo "   📍 Chemin : $ffmpegPath\n";
    echo "   📦 Version : $ffmpegVersion\n\n";
} else {
    echo "   ⚠️  FFmpeg n'est pas installé (optionnel mais recommandé)\n";
    echo "   💡 Installation : brew install ffmpeg\n";
    echo "   ℹ️  Sans FFmpeg, la qualité audio sera moins bonne\n\n";
}

// 3. Vérifier le dossier de destination
echo "3️⃣  Vérification du dossier de destination...\n";
$uploadDir = __DIR__ . '/../public/music/uploads/';

if (!is_dir($uploadDir)) {
    echo "   ⚠️  Le dossier n'existe pas\n";
    echo "   🔧 Création du dossier...\n";
    mkdir($uploadDir, 0755, true);
    echo "   ✅ Dossier créé : $uploadDir\n\n";
} else {
    echo "   ✅ Dossier existe : $uploadDir\n";
}

if (is_writable($uploadDir)) {
    echo "   ✅ Dossier accessible en écriture\n\n";
} else {
    echo "   ❌ Dossier non accessible en écriture\n";
    echo "   💡 Solution : chmod 755 public/music/uploads\n\n";
    $allGood = false;
}

// 4. Vérifier la base de données
echo "4️⃣  Vérification de la base de données...\n";
$dbFile = __DIR__ . '/../var/data.db';

if (file_exists($dbFile)) {
    echo "   ✅ Base de données trouvée : $dbFile\n\n";
} else {
    echo "   ⚠️  Base de données non trouvée\n";
    echo "   💡 Exécutez : php bin/console doctrine:migrations:migrate\n\n";
}

// 5. Test de connexion YouTube
if ($ytDlpFound) {
    echo "5️⃣  Test de connexion YouTube...\n";
    $testUrl = 'https://www.youtube.com/watch?v=jNQXAC9IVRw'; // "Me at the zoo" - 1ère vidéo YouTube

    $process = new Process([
        $ytDlpPath,
        '--dump-json',
        '--no-playlist',
        $testUrl
    ]);
    $process->setTimeout(30);
    $process->run();

    if ($process->isSuccessful()) {
        $data = json_decode($process->getOutput(), true);
        if ($data && isset($data['title'])) {
            echo "   ✅ Connexion YouTube OK\n";
            echo "   🎬 Test avec : " . $data['title'] . "\n\n";
        } else {
            echo "   ⚠️  Réponse inattendue de YouTube\n\n";
        }
    } else {
        echo "   ❌ Impossible de se connecter à YouTube\n";
        echo "   💡 Vérifiez votre connexion internet\n\n";
        $allGood = false;
    }
}

// Résumé final
<?php

declare(strict_types=1);

// Point d'entrée Vercel serverless → Symfony
if (isset($_ENV['VERCEL']) || isset($_ENV['NOW_REGION'])) {
    // SQLite : copier la BDD vers /tmp (seul répertoire accessible en écriture sur Vercel)
    $sourceDb = dirname(__DIR__) . '/var/data.db';
    $targetDb = '/tmp/data.db';

    if (!file_exists($targetDb)) {
        if (file_exists($sourceDb)) {
            // Copier la BDD commitée dans /tmp
            copy($sourceDb, $targetDb);
        } else {
            // Aucune BDD disponible : créer un fichier SQLite vide
            // Doctrine créera les tables via le schéma au prochain accès
            touch($targetDb);
        }
    }
}

chdir(dirname(__DIR__));
return require dirname(__DIR__) . '/public/index.php';

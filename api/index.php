<?php

declare(strict_types=1);

// Point d'entrée Vercel serverless → Symfony
if (isset($_ENV['VERCEL']) || isset($_ENV['NOW_REGION'])) {
    // Copier la base SQLite vers /tmp (seul répertoire accessible en écriture sur Vercel)
    if (!file_exists('/tmp/data.db') && file_exists(dirname(__DIR__) . '/var/data.db')) {
        copy(dirname(__DIR__) . '/var/data.db', '/tmp/data.db');
    }

    // Symfony cherche un fichier .env au démarrage.
    // Sur Vercel, ce fichier n'existe pas (ignoré par git).
    // On injecte les variables nécessaires directement dans $_ENV / $_SERVER
    // AVANT que autoload_runtime.php ne tente de lire .env.
    $requiredVars = [
        'APP_ENV'      => 'prod',
        'APP_DEBUG'    => '0',
        'APP_SECRET'   => $_ENV['APP_SECRET'] ?? $_SERVER['APP_SECRET'] ?? 'vercel-secret-placeholder-change-me',
        'DATABASE_URL' => $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? 'sqlite:////tmp/data.db',
    ];

    foreach ($requiredVars as $key => $value) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }

    // Créer un .env vide dans /tmp pour satisfaire Symfony Dotenv
    // (Dotenv::bootEnv n'échoue pas si le fichier existe mais est vide)
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) {
        file_put_contents('/tmp/.env', '');
        // Redéfinir le chemin du projet pour que Symfony cherche dans /tmp
        // Alternative : créer un lien symbolique ou utiliser putenv
        // Solution plus propre : créer le fichier directement dans le répertoire projet
        // (le répertoire de déploiement Vercel est accessible en lecture seule,
        //  donc on tente quand même d'écrire — si ça échoue, ce n'est pas bloquant)
        @file_put_contents($envFile, "APP_ENV=prod\nAPP_DEBUG=0\n");
    }
}

chdir(dirname(__DIR__));
return require dirname(__DIR__) . '/public/index.php';

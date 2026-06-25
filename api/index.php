<?php

declare(strict_types=1);

// Point d'entrée Vercel serverless → Symfony
if (isset($_ENV['VERCEL']) || isset($_ENV['NOW_REGION'])) {
    // SQLite : copier la BDD vers /tmp (seul répertoire accessible en écriture sur Vercel)
    if (!file_exists('/tmp/data.db') && file_exists(dirname(__DIR__) . '/var/data.db')) {
        copy(dirname(__DIR__) . '/var/data.db', '/tmp/data.db');
    }
}

chdir(dirname(__DIR__));
return require dirname(__DIR__) . '/public/index.php';

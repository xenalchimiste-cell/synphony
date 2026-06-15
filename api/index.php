<?php

declare(strict_types=1);

// Point d'entrée Vercel serverless → Symfony
chdir(dirname(__DIR__));
require dirname(__DIR__) . '/public/index.php';

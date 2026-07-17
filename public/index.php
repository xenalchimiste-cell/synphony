<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    if (isset($_SERVER['POSTGRES_URL'])) {
        $_SERVER['DATABASE_URL'] = $_SERVER['POSTGRES_URL'];
        $context['DATABASE_URL'] = $_SERVER['POSTGRES_URL'];
        putenv('DATABASE_URL=' . $_SERVER['POSTGRES_URL']);
    }
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

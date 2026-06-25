<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function getCacheDir(): string
    {
        if (isset($_ENV['VERCEL']) || isset($_ENV['NOW_REGION'])) {
            return '/tmp/cache/' . $this->environment;
        }
        return parent::getCacheDir();
    }

    public function getLogDir(): string
    {
        if (isset($_ENV['VERCEL']) || isset($_ENV['NOW_REGION'])) {
            return '/tmp/log';
        }
        return parent::getLogDir();
    }
}

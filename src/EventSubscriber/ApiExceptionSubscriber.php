<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', 200],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();

        $apiPrefixes = ['/spotify/', '/deezer/', '/youtube/', '/upload', '/delete/', '/audio/'];

        foreach ($apiPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $e = $event->getThrowable();
                $event->setResponse(new JsonResponse([
                    'error' => $e->getMessage(),
                    'trace' => $_SERVER['APP_ENV'] === 'dev' ? $e->getTraceAsString() : null,
                ], 500));
                $event->stopPropagation();
                return;
            }
        }
    }
}

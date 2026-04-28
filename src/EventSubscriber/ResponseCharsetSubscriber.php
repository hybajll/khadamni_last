<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ResponseCharsetSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        // Force UTF-8 for text responses to avoid mojibake (common on Windows/XAMPP).
        $contentType = (string) $response->headers->get('Content-Type', '');
        $mime = trim(strtolower(strtok($contentType, ';') ?: ''));

        if ($mime === '' || !str_starts_with($mime, 'text/')) {
            return;
        }

        $response->setCharset('UTF-8');
        $response->headers->set('Content-Type', $mime.'; charset=UTF-8');
    }
}


<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds security-relevant HTTP response headers to every main request.
 * Sub-requests (fragments, ESI) are intentionally skipped.
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'onKernelResponse'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $headers = $event->getResponse()->headers;

        // Prevent MIME-type sniffing
        $headers->set('X-Content-Type-Options', 'nosniff');

        // Deny framing to block clickjacking
        $headers->set('X-Frame-Options', 'DENY');

        // Limit referrer information to same origin
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable browser features not needed by this app
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy — tightened for a server-rendered dashboard with no inline eval
        // Adjust 'cdn.jsdelivr.net' entry if you remove the FrankenPHP hot-reload CDN scripts.
        $headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' https://cdn.jsdelivr.net; "
            . "style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data:; "
            . "font-src 'self'; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self';"
        );
    }
}

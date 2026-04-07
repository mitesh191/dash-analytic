<?php

declare(strict_types=1);

namespace App\Messenger\Middleware;

use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

/**
 * Measures wall-clock time for every message on the bus.
 * Logs a WARNING for anything exceeding SLOW_THRESHOLD_MS so that
 * slow handlers surface in production monitoring without requiring
 * a full APM tool.
 */
final class PerformanceMonitoringMiddleware implements MiddlewareInterface
{
    private const SLOW_THRESHOLD_MS = 500;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $messageClass = $envelope->getMessage()::class;
        $start        = hrtime(true);

        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            $durationMs = (int) round((hrtime(true) - $start) / 1_000_000);

            $context = [
                'message'     => $messageClass,
                'duration_ms' => $durationMs,
            ];

            if ($durationMs >= self::SLOW_THRESHOLD_MS) {
                $this->logger->warning('Slow message handling detected', $context);
            } else {
                $this->logger->debug('Message handled', $context);
            }
        }
    }
}

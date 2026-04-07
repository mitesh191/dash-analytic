<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * In-memory Monolog handler that buffers the last N records for the
 * current request so the dashboard UI can display them directly.
 * Only records on the "dashboard" channel are captured.
 */
final class DashboardRequestLogHandler extends AbstractProcessingHandler
{
    /** @var array<int, array{level: string, message: string, context: array<string,mixed>, time: string}> */
    private array $records = [];

    private const MAX_RECORDS = 20;

    public function __construct()
    {
        parent::__construct(Level::Debug);
    }

    protected function write(LogRecord $record): void
    {
        if (count($this->records) >= self::MAX_RECORDS) {
            array_shift($this->records);
        }

        $this->records[] = [
            'level'   => $record->level->getName(),
            'message' => $record->message,
            'context' => $record->context,
            'time'    => $record->datetime->format('H:i:s.v'),
        ];
    }

    /** @return array<int, array{level: string, message: string, context: array<string,mixed>, time: string}> */
    public function getRecords(): array
    {
        return $this->records;
    }
}

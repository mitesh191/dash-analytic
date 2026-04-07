<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\ValueObject;

enum MetricStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
    case Degraded = 'degraded';
}

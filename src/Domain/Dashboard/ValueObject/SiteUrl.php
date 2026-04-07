<?php

declare(strict_types=1);

namespace App\Domain\Dashboard\ValueObject;

use InvalidArgumentException;

final class SiteUrl
{
    private readonly string $value;

    public function __construct(string $value)
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException("Invalid site URL: {$value}");
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}

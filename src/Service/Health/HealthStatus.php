<?php

declare(strict_types=1);

namespace Directive\Service\Health;

enum HealthStatus
{
    case Pass;
    case Warn;
    case Fail;

    public function value(): string
    {
        return match ($this) {
            HealthStatus::Pass => 'pass',
            HealthStatus::Warn => 'warn',
            HealthStatus::Fail => 'fail',
        };
    }

    /** Returns the worst (highest severity) status from the given list. */
    public static function worst(self ...$statuses): self
    {
        $result = self::Pass;
        foreach ($statuses as $status) {
            if ($status === self::Fail) {
                return self::Fail;
            }
            if ($status === self::Warn) {
                $result = self::Warn;
            }
        }
        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Directive\Service\Maintenance;

use JsonSerializable;

/** Immutable value object representing a maintenance-mode snapshot. */
final class MaintenanceInfo implements JsonSerializable
{
    public function __construct(
        private readonly bool $mode,
        private readonly int $period,
        private readonly string $message,
    ) {}

    public function getMode(): bool
    {
        return $this->mode;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'mode'    => $this->mode,
            'period'  => $this->period,
            'message' => $this->message,
        ];
    }

    /**
     * Build from a raw data array (e.g. parsed JSON).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mode: (bool) ($data['mode']    ?? false),
            period: (int) ($data['period']  ?? 0),
            message: (string) ($data['message'] ?? ''),
        );
    }
}

<?php

declare(strict_types=1);

namespace Directive\Service\Health;

interface HealthCheckInterface
{
    /**
     * Run the health check.
     *
     * The returned array MUST contain at least the key "status"
     * with one of the IETF values: "pass", "warn", or "fail".
     *
     * @return array<string, mixed>
     */
    public function check(): array;
}

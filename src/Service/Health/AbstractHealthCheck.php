<?php

declare(strict_types=1);

namespace Directive\Service\Health;

abstract class AbstractHealthCheck implements HealthCheckInterface
{
    /**
     * Execute the check, automatically measuring duration and adding timing metadata.
     *
     * @return array<string, mixed>
     */
    final public function check(): array
    {
        $start  = hrtime(true);
        $result = $this->run();
        $durationMs = (int) ((hrtime(true) - $start) / 1_000_000);

        return array_merge($result, [
            "time"        => date("c"),
            "duration_ms" => $durationMs,
        ]);
    }

    /**
     * Implement the actual check logic.
     *
     * MUST return an array with at least a "status" key ("pass"/"warn"/"fail").
     *
     * @return array<string, mixed>
     */
    abstract protected function run(): array;
}

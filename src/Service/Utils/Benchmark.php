<?php

declare(strict_types=1);

namespace Directive\Service\Utils;

use JsonSerializable;

final class Benchmark implements BenchmarkInterface, JsonSerializable
{
    private float $timeref = 0.0;

    /** @var array<int, array<string, mixed>> */
    private array $points = [];

    public function __construct(private readonly bool $enabled = true)
    {
    }

    public function start(): void
    {
        $this->timeref = microtime(true);
        $this->points  = [];
        if ($this->enabled) {
            $this->points[] = [
                'label' => 'initial starting point',
                'time'  => $this->timeref,
            ];
        }
    }

    public function addPoint(string $label, ?float $time = null): void
    {
        if ($this->enabled) {
            $this->points[] = [
                'label' => $label,
                'time'  => $time ?? microtime(true),
            ];
        }
    }

    public function havePoints(): bool
    {
        return count($this->points) > 1;
    }

    /** @return array<int, array<string, mixed>> */
    public function getPoints(): array
    {
        $this->calculate();
        return $this->points;
    }

    public function getFullTimeExecution(): float
    {
        return microtime(true) - $this->timeref;
    }

    /** @return array<int, array<string, mixed>> */
    public function jsonSerialize(): array
    {
        return $this->getPoints();
    }

    private function calculate(): void
    {
        if ($this->enabled) {
            foreach ($this->points as $idx => $point) {
                $this->points[$idx]['elapsed'] = $idx === 0
                    ? 0.0
                    : $point['time'] - $this->points[$idx - 1]['time'];
            }
        }
    }
}

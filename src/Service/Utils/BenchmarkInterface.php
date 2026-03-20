<?php

declare(strict_types=1);

namespace Directive\Service\Utils;

/**
 * Simple execution-time benchmark utility.
 * Concrete implementation lives in Epic 8.
 */
interface BenchmarkInterface
{
    /** Start (or restart) global timing. */
    public function start(): void;

    /** Get total elapsed time in milliseconds since start(). */
    public function getFullTimeExecution(): float;

    /** Record a named timing point (current microtime used when $time is null). */
    public function addPoint(string $label, ?float $time = null): void;

    /** Return true when at least one named point has been recorded. */
    public function havePoints(): bool;

    /**
     * Return all named benchmark points with elapsed deltas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPoints(): array;
}

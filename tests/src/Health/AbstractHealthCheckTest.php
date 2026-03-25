<?php

declare(strict_types=1);

use Directive\Service\Health\AbstractHealthCheck;

// ---------------------------------------------------------------------------
// Test double — minimal concrete check
// ---------------------------------------------------------------------------

final class AlwaysPassCheck extends AbstractHealthCheck
{
    protected function run(): array
    {
        return ['status' => 'pass', 'detail' => 'ok'];
    }
}

final class SleepyCheck extends AbstractHealthCheck
{
    protected function run(): array
    {
        // Simulate minimal work
        usleep(1000); // 1 ms
        return ['status' => 'pass'];
    }
}

// ------------------------------------------------------------------
// check() — timing metadata injection
// ------------------------------------------------------------------

it('check() result contains "status", "time", and "duration_ms" keys', function (): void {
    $check  = new AlwaysPassCheck();
    $result = $check->check();

    expect($result)->toHaveKey('status', 'pass');
    expect($result)->toHaveKey('detail', 'ok');
    expect($result)->toHaveKey('time');
    expect($result)->toHaveKey('duration_ms');
});

it('check() duration_ms is a non-negative integer', function (): void {
    $check  = new AlwaysPassCheck();
    $result = $check->check();

    expect($result['duration_ms'])->toBeInt();
    expect($result['duration_ms'])->toBeGreaterThanOrEqual(0);
});

it('check() time is an ISO 8601 formatted datetime string', function (): void {
    $check  = new AlwaysPassCheck();
    $result = $check->check();

    expect($result['time'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

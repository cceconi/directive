<?php

declare(strict_types=1);

use Directive\Service\Health\HealthStatus;

// ------------------------------------------------------------------
// value()
// ------------------------------------------------------------------

it('Pass->value() returns "pass"', function (): void {
    expect(HealthStatus::Pass->value())->toBe('pass');
});

it('Warn->value() returns "warn"', function (): void {
    expect(HealthStatus::Warn->value())->toBe('warn');
});

it('Fail->value() returns "fail"', function (): void {
    expect(HealthStatus::Fail->value())->toBe('fail');
});

// ------------------------------------------------------------------
// worst() — aggregation rules
// ------------------------------------------------------------------

it('worst() with only Pass returns Pass', function (): void {
    expect(HealthStatus::worst(HealthStatus::Pass, HealthStatus::Pass))->toBe(HealthStatus::Pass);
});

it('worst() with Warn and Pass returns Warn', function (): void {
    expect(HealthStatus::worst(HealthStatus::Pass, HealthStatus::Warn, HealthStatus::Pass))->toBe(HealthStatus::Warn);
});

it('worst() with Fail among others returns Fail immediately', function (): void {
    expect(HealthStatus::worst(HealthStatus::Pass, HealthStatus::Fail, HealthStatus::Warn))->toBe(HealthStatus::Fail);
});

it('worst() with no arguments returns Pass', function (): void {
    expect(HealthStatus::worst())->toBe(HealthStatus::Pass);
});

<?php

declare(strict_types=1);

use Directive\Service\Health\HealthCheckResponse;
use Directive\Service\Health\HealthStatus;

// ------------------------------------------------------------------
// toArray() — IETF serialisation
// ------------------------------------------------------------------

it('toArray() contains status, version, releaseId and checks keys', function (): void {
    $response = new HealthCheckResponse(
        status:    HealthStatus::Pass,
        version:   '1.0.0',
        releaseId: 'abc123',
    );

    $array = $response->toArray();

    expect($array)->toHaveKey('status', 'pass');
    expect($array)->toHaveKey('version', '1.0.0');
    expect($array)->toHaveKey('releaseId', 'abc123');
    expect($array)->toHaveKey('checks', []);
});

it('toArray() serialises Fail status as "fail"', function (): void {
    $response = new HealthCheckResponse(HealthStatus::Fail, '2.0.0', 'def456');
    expect($response->toArray()['status'])->toBe('fail');
});

it('toArray() omits description key when description is null', function (): void {
    $response = new HealthCheckResponse(HealthStatus::Pass, '1.0.0', '');
    $array    = $response->toArray();
    expect($array)->not->toHaveKey('description');
});

it('toArray() includes description key when description is set', function (): void {
    $response = new HealthCheckResponse(
        status:      HealthStatus::Pass,
        version:     '1.0.0',
        releaseId:   '',
        checks:      [],
        description: 'All good',
    );
    expect($response->toArray())->toHaveKey('description', 'All good');
});

it('toArray() includes checks when provided', function (): void {
    $checks = ['db' => [['status' => 'pass', 'time' => '2026-03-25T10:00:00+00:00', 'duration_ms' => 5]]];
    $response = new HealthCheckResponse(HealthStatus::Pass, '1.0.0', '', $checks);
    expect($response->toArray()['checks'])->toBe($checks);
});

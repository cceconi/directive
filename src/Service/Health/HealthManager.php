<?php

declare(strict_types=1);

namespace Directive\Service\Health;

use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\Maintenance\MaintenanceManagerInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class HealthManager
{
    public function __construct(
        private readonly AppInfoInterface $appInfo,
        private readonly MaintenanceManagerInterface $maintenanceManager,
        private readonly Psr17Factory $psr17Factory,
    ) {}

    /**
     * Handle GET /health/live.
     *
     * Always returns "pass" if the process is running and the feature is not disabled.
     * No external dependency checks — the probe answers "is the process alive?".
     */
    public function resolveLive(ResponseInterface $response): ResponseInterface
    {
        if ($this->isDisabled()) {
            return $response->withStatus(404);
        }

        $healthResponse = new HealthCheckResponse(
            status:    HealthStatus::Pass,
            version:   $this->appInfo->getVersion(),
            releaseId: '',
            checks:    [],
        );

        return $this->buildJsonResponse($response, $healthResponse);
    }

    /**
     * Handle GET /health/ready.
     *
     * Aggregates application-level checks. Returns "fail" (503) if maintenance
     * is active or if any provided check returns "fail".
     *
     * @param array<string, HealthCheckInterface> $checks
     */
    public function resolveReady(ResponseInterface $response, array $checks): ResponseInterface
    {
        if ($this->isDisabled()) {
            return $response->withStatus(404);
        }

        $aggregatedChecks = [];
        $statuses         = [];

        // Maintenance check takes precedence — always evaluated first.
        if ($this->maintenanceManager->isActive()) {
            $aggregatedChecks['maintenance'] = [
                ['status' => HealthStatus::Fail->value(), 'message' => $this->maintenanceManager->getMessage()],
            ];
            $statuses[] = HealthStatus::Fail;
        }

        // Application-level checks.
        foreach ($checks as $name => $check) {
            $result                    = $check->check();
            $aggregatedChecks[$name][] = $result;
            $statusStr                 = $result['status'] ?? 'fail';
            $statuses[]                = $this->parseStatus($statusStr);
        }

        $globalStatus = HealthStatus::worst(...$statuses !== [] ? $statuses : [HealthStatus::Pass]);

        $healthResponse = new HealthCheckResponse(
            status:    $globalStatus,
            version:   $this->appInfo->getVersion(),
            releaseId: '',
            checks:    $aggregatedChecks,
        );

        return $this->buildJsonResponse($response, $healthResponse);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function isDisabled(): bool
    {
        return ($_ENV['HEALTH_DISABLED'] ?? 'false') === 'true';
    }

    private function parseStatus(string $status): HealthStatus
    {
        return match ($status) {
            'fail'  => HealthStatus::Fail,
            'warn'  => HealthStatus::Warn,
            default => HealthStatus::Pass,
        };
    }

    private function buildJsonResponse(ResponseInterface $response, HealthCheckResponse $healthResponse): ResponseInterface
    {
        $body = json_encode($healthResponse->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stream = $this->psr17Factory->createStream($body !== false ? $body : '{}');

        $httpStatus = $healthResponse->status === HealthStatus::Fail ? 503 : 200;

        return $response
            ->withStatus($httpStatus)
            ->withHeader('Content-Type', 'application/health+json')
            ->withBody($stream);
    }
}

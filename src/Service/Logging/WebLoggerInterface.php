<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Security\WebUserInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Structured request logger.
 * One instance per HTTP request — accumulates entries, flushed via write().
 */
interface WebLoggerInterface
{
    /** Log the running application version. */
    public function logVersion(string $version): void;

    /** Log total business execution time (seconds). */
    public function logBusinessTimeExecution(float $elapsed): void;

    /**
     * Log detailed benchmark points.
     *
     * @param array<int, array<string, mixed>> $points
     */
    public function logBusinessBenchmark(array $points): void;

    /** Add a raw message to the in-memory log stack. */
    public function logRaw(string $message, mixed $context = null): void;

    /** Log the incoming HTTP request. */
    public function logRequest(string $url, ServerRequestInterface $request): void;

    /** Log the outgoing HTTP response. */
    public function logResponse(ResponseInterface $response, string $message, mixed $data = null): void;

    /** Log the authenticated web user (no-op when null or guest). */
    public function logWebUser(?WebUserInterface $webUser): void;

    /** Log the JWT token string used for this request. */
    public function logJwt(string $jwt): void;

    /** Log the resolved API version name/status/info. */
    public function logApiVersion(string $name, string $status, string $info = ''): void;

    /** Flush accumulated log entries to the configured handler. */
    public function write(): void;

    /** Log a throwable at error level. */
    public function logError(\Throwable $e): void;
}

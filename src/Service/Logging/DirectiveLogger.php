<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Directive\Service\Security\WebUserInterface;

/**
 * Framework logger built on top of Monolog.
 *
 * Handler strategy is determined by LoggingConfigInterface::getLogStrategy():
 *   - Immediate      : every record is written to stdout via a StreamHandler.
 *   - BufferedOnError: records are buffered in a FingersCrossedHandler and
 *                      are only flushed to the StreamHandler when an ERROR (or
 *                      above) is emitted. Buffer capacity is controlled by
 *                      LoggingConfigInterface::getLogBufferSize() (default 200).
 *
 * A DirectiveContextProcessor is automatically attached so that `request_id`,
 * `env`, and `app_version` appear in `extra` on every record.
 */
final class DirectiveLogger extends Logger
{
    public function __construct(LoggingConfigInterface $config, RequestIdHolder $holder)
    {
        $streamHandler = new StreamHandler('php://stdout');
        $streamHandler->setFormatter(new JsonFormatter());

        $handler = match ($config->getLogStrategy()) {
            LogStrategy::Immediate => $streamHandler,
            LogStrategy::BufferedOnError => new FingersCrossedHandler(
                $streamHandler,
                new ErrorLevelActivationStrategy(Level::Error),
                $config->getLogBufferSize(),
            ),
        };

        parent::__construct(
            name: $config->getAppCode(),
            handlers: [$handler],
            processors: [new DirectiveContextProcessor($holder, $config)],
        );
    }

    // -------------------------------------------------------------------------
    // Semantic helpers
    // -------------------------------------------------------------------------

    /**
     * Log an incoming HTTP request at DEBUG level.
     */
    public function logRequest(ServerRequestInterface $request): void
    {
        $this->debug('http.request', [
            'method' => $request->getMethod(),
            'uri'    => (string) $request->getUri(),
        ]);
    }

    /**
     * Log an outgoing HTTP response at DEBUG level.
     */
    public function logResponse(ResponseInterface $response): void
    {
        $this->debug('http.response', [
            'status' => $response->getStatusCode(),
        ]);
    }

    /**
     * Log the authenticated user at DEBUG level.
     * No-op when the user is null or unauthenticated (guest).
     */
    public function logWebUser(?WebUserInterface $webUser): void
    {
        if ($webUser === null || $webUser->isGuest()) {
            return;
        }

        $this->debug('http.user', [
            'user_id'   => $webUser->getId(),
            'user_name' => $webUser->getFullName(),
            'user_role' => $webUser->getRole()::class,
        ]);
    }

    /**
     * Log a throwable at ERROR level.
     */
    public function logError(\Throwable $e): void
    {
        $this->error($e::class, [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ]);
    }

    /**
     * Log a performance benchmark at DEBUG level.
     *
     * @param array<int, array<string, mixed>> $points
     */
    public function logBenchmark(array $points, float $timeExecution): void
    {
        $this->debug('benchmark', [
            'points'         => $points,
            'time_execution' => $timeExecution,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Security\WebUserInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConsoleLogger implements ConsoleLoggerInterface
{
    public const string EVENT_KEY = 'APISY_CONSOLELOG';

    private readonly Logger $logger;
    private float $timeref;

    /** @var array<int, array<string, mixed>> */
    private array $logdata = [];

    /** @var mixed[] */
    private array $error = [];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $benchmark = null;

    private ?float $timeExecution = null;

    private ?string $version = null;

    public function __construct(
        private readonly string $channel,
        private readonly string $logDir,
        private readonly ConfigurationInterface $config,
        ?float $timeref = null,
    ) {
        $this->timeref = $timeref ?? microtime(true);

        $handler = new RotatingFileHandler($this->logDir . '/apisy_console.log', 30);
        $formatter = new JsonFormatter();
        $formatter->includeStacktraces(true);
        $handler->setFormatter($formatter);

        $this->logger = new Logger($this->channel);
        $this->logger->pushHandler($handler);
        $this->logger->pushProcessor(new MemoryPeakUsageProcessor());
    }

    // ── WebLoggerInterface ───────────────────────────────────────────────────

    public function logVersion(string $version): void
    {
        $this->version = $version;
    }

    public function logBusinessTimeExecution(float $elapsed): void
    {
        $this->timeExecution = $elapsed;
    }

    /** @param array<int, array<string, mixed>> $points */
    public function logBusinessBenchmark(array $points): void
    {
        $this->benchmark = $points;
    }

    public function logRaw(string $message, mixed $context = null): void
    {
        $this->logdata[] = ['message' => $message, 'context' => $context];
    }

    /** No-op for console — no HTTP request. */
    public function logRequest(string $url, ServerRequestInterface $request): void {}

    /** No-op for console — no HTTP response. */
    public function logResponse(ResponseInterface $response, string $message, mixed $data = null): void {}

    /** No-op for console — no web user. */
    public function logWebUser(?WebUserInterface $webUser): void {}

    /** No-op for console — no JWT. */
    public function logJwt(string $jwt): void {}

    /** No-op for console — no API version context. */
    public function logApiVersion(string $name, string $status, string $info = ''): void {}

    public function write(): void
    {
        $endTime = microtime(true);

        $data = [
            'server'             => gethostname() !== false ? gethostname() : 'N/A',
            'app'                => $this->config->get('app.code'),
            'mode'               => $this->config->get('env.code'),
            'version'            => $this->version,
            '@timestamp'         => date('c', (int) $this->timeref),
            'started_at'         => $this->timeref,
            'ended_at'           => $endTime,
            'console_time'       => $endTime - $this->timeref,
            'business_benchmark' => $this->benchmark,
            'business_timeexec'  => $this->timeExecution,
            'log'                => $this->logdata,
            'error'              => $this->error,
        ];

        $this->logger->info(static::EVENT_KEY, $data);
        $this->logdata = [];
    }

    public function logError(\Throwable $e): void
    {
        $this->error[] = [
            'class'   => $e::class,
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
        ];
    }
}

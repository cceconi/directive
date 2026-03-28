<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Security\WebUserInterface;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WebLogger implements WebLoggerInterface
{
    public const string EVENT_KEY = 'APISY_WEBLOG';

    private readonly Logger $logger;
    private float $timeref;

    /** @var array<int, array<string, mixed>> */
    private array $logdata = [];

    /** @var array<string, mixed> */
    private array $request = [];

    /** @var array<string, mixed> */
    private array $response = [];

    /** @var mixed[] */
    private array $error = [];

    /** @var array<int, array<string, mixed>>|null */
    private ?array $benchmark = null;

    private ?float $timeExecution = null;

    private ?string $version = null;

    private ?string $tokenAuth = null;

    /** @var array<string, mixed> */
    private array $user = ['name' => 'Guest', 'id' => null];

    public function __construct(
        private readonly string $channel,
        private readonly string $logDir,
        private readonly LoggingConfigInterface $config,
        ?float $timeref = null,
    ) {
        $this->timeref = $timeref ?? microtime(true);

        $handler = new RotatingFileHandler($this->logDir . '/apisy_web.log', 30);
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

    public function logRequest(string $url, ServerRequestInterface $request): void
    {
        $this->request = [
            'api' => [
                'url'    => $url,
                'scheme' => $request->getUri()->getScheme(),
                'host'   => $request->getUri()->getHost(),
                'method' => $request->getMethod(),
            ],
            'headers' => $request->getHeaders(),
            'cookies' => $request->getCookieParams(),
        ];
    }

    public function logResponse(ResponseInterface $response, string $message, mixed $data = null): void
    {
        $this->response = [
            'headers' => $response->getHeaders(),
            'code'    => $response->getStatusCode(),
            'message' => $message,
            'data'    => $data,
        ];
    }

    public function logWebUser(?WebUserInterface $webUser): void
    {
        if ($webUser !== null && $webUser->isAuthenticated()) {
            $this->user = [
                'name' => $webUser->getFullName(),
                'id'   => $webUser->getId(),
            ];
        }
    }

    public function logJwt(string $jwt): void
    {
        $this->tokenAuth = $jwt;
    }

    public function logApiVersion(string $name, string $status, string $info = ''): void
    {
        if (isset($this->request['api']) && is_array($this->request['api'])) {
            $this->request['api']['version'] = [
                'name'   => $name,
                'status' => $status,
                'info'   => $info,
            ];
        }
    }

    public function write(): void
    {
        $endTime = microtime(true);

        $data = [
            'server'              => gethostname() !== false ? gethostname() : 'N/A',
            'app'                 => $this->config->getAppCode(),
            'mode'                => $this->config->getEnvCode(),
            'version'             => $this->version,
            '@timestamp'          => date('c', (int) $this->timeref),
            'started_at'          => $this->timeref,
            'ended_at'            => $endTime,
            'request_time'        => $endTime - $this->timeref,
            'business_benchmark'  => $this->benchmark,
            'business_timeexec'   => $this->timeExecution,
            'user'                => $this->tokenAuth !== null
                ? array_merge($this->user, ['jwt' => $this->tokenAuth])
                : $this->user,
            'log'                 => $this->logdata,
            'error'               => $this->error,
            'request'             => $this->request,
            'response'            => $this->response,
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

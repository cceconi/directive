<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultLoggingConfig implements LoggingConfigInterface
{
    public function __construct(private AbstractConfiguration $config) {
        $this->define();
    }

    protected function define(): void
    {
        $this->config->optional('LOG_PATH', 'var/log', 'string');
        $this->config->optional('LOG_STRATEGY', 'immediate', 'string');
        $this->config->optional('LOG_BUFFER_SIZE', 200, 'int');
    }

    public function getLogPath(): string
    {
        return (string) $this->config->get('LOG_PATH');
    }

    public function getAppCode(): string
    {
        return (string) $this->config->get('APP_CODE');
    }

    public function getAppEnv(): string
    {
        return (string) $this->config->get('APP_ENV');
    }

    public function getAppVersion(): string
    {
        return (string) $this->config->get('APP_VERSION');
    }

    public function getLogStrategy(): LogStrategy
    {
        return LogStrategy::from((string) $this->config->get('LOG_STRATEGY'));
    }

    public function getLogBufferSize(): int
    {
        return (int) $this->config->get('LOG_BUFFER_SIZE');
    }
}

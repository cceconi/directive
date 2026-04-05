<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultLoggingConfig implements LoggingConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('LOG_PATH', 'var/log', 'string');
        $config->optional('LOG_STRATEGY', 'immediate', 'string');
        $config->optional('LOG_BUFFER_SIZE', 200, 'int');
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

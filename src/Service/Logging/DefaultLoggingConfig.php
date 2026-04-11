<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultLoggingConfig implements LoggingConfigInterface, ConfigProviderInterface
{
    public function __construct(
        private AppIdentityConfigInterface $appIdentity,
        private Configuration $config,
    ) {}

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
        return $this->appIdentity->getAppCode();
    }

    public function getAppEnv(): string
    {
        return $this->appIdentity->getAppEnv();
    }

    public function getAppVersion(): string
    {
        return $this->appIdentity->getAppVersion();
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

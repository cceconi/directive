<?php

declare(strict_types=1);

namespace Directive\Service\Logging;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultLoggingConfig extends AbstractConfiguration implements LoggingConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_LOG_PATH', '/var/log/directive', 'string');
        $this->optional('DIRECTIVE_APP_CODE', 'app', 'string');
        $this->optional('DIRECTIVE_ENV_CODE', 'prod', 'string');
    }

    public function getLogPath(): string
    {
        return (string) $this->get('DIRECTIVE_LOG_PATH');
    }

    public function getAppCode(): string
    {
        return (string) $this->get('DIRECTIVE_APP_CODE');
    }

    public function getEnvCode(): string
    {
        return (string) $this->get('DIRECTIVE_ENV_CODE');
    }
}

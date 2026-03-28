<?php

declare(strict_types=1);

namespace Directive\Service\AppIdentity;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultAppIdentityConfig extends AbstractConfiguration implements AppIdentityConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_APP_CODE', 'app', 'string');
        $this->optional('DIRECTIVE_APP_NAME', 'Directive App', 'string');
        $this->optional('DIRECTIVE_APP_VERSION', '1.0.0', 'string');
        $this->optional('DIRECTIVE_APP_DESCRIPTION', '', 'string');
        $this->optional('DIRECTIVE_APP_URL', '', 'string');
    }

    public function getAppCode(): string
    {
        return (string) $this->get('DIRECTIVE_APP_CODE');
    }

    public function getAppName(): string
    {
        return (string) $this->get('DIRECTIVE_APP_NAME');
    }

    public function getAppVersion(): string
    {
        return (string) $this->get('DIRECTIVE_APP_VERSION');
    }

    public function getAppDescription(): string
    {
        return (string) $this->get('DIRECTIVE_APP_DESCRIPTION');
    }

    public function getAppUrl(): string
    {
        return (string) $this->get('DIRECTIVE_APP_URL');
    }
}

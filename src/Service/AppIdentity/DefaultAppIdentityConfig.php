<?php

declare(strict_types=1);

namespace Directive\Service\AppIdentity;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultAppIdentityConfig implements AppIdentityConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('APP_CODE', 'app', 'string');
        $config->optional('APP_NAME', 'Directive App', 'string');
        $config->optional('APP_ENV', 'dev', 'string');
        $config->optional('APP_ENV_PROD_NAME', 'prod', 'string');
        $config->optional('APP_VERSION', '1.0.0', 'string');
        $config->optional('APP_DESCRIPTION', '', 'string');
        $config->optional('APP_URL', '', 'string');
    }

    public function getAppCode(): string
    {
        return (string) $this->config->get('APP_CODE');
    }

    public function getAppName(): string
    {
        return (string) $this->config->get('APP_NAME');
    }

    public function getAppEnv(): string
    {
        return (string) $this->config->get('APP_ENV');
    }

    public function getAppVersion(): string
    {
        return (string) $this->config->get('APP_VERSION');
    }

    public function getAppDescription(): string
    {
        return (string) $this->config->get('APP_DESCRIPTION');
    }

    public function getAppUrl(): string
    {
        return (string) $this->config->get('APP_URL');
    }
}

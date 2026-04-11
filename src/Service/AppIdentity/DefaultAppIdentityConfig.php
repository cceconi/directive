<?php

declare(strict_types=1);

namespace Directive\Service\AppIdentity;

use Directive\Service\AppManagement\AppInfo;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultAppIdentityConfig implements AppIdentityConfigInterface, ConfigProviderInterface
{
    public function __construct(
        private readonly AppInfo $appInfo,
        private readonly Configuration $config,
    ) {}

    public function define(Configuration $config): void
    {
        $config->optional('APP_ENV', 'development', 'string');
        $config->optional('APP_ENV_PROD_NAME', 'production', 'string');
        $config->optional('APP_DESCRIPTION', '', 'string');
        $config->optional('APP_URL', '', 'string');
        $config->optional('MANAGEMENT_TOKEN', '', 'string');
    }

    public function getAppCode(): string
    {
        $name = $this->appInfo->getName();
        if ($name === '') {
            return '';
        }
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($name)), '-');
    }

    public function getAppName(): string
    {
        return $this->appInfo->getName();
    }

    public function getAppEnv(): string
    {
        return (string) $this->config->get('APP_ENV');
    }

    public function getAppVersion(): string
    {
        return $this->appInfo->getVersion();
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

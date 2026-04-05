<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultSecurityConfig implements SecurityConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('SECURITY_TOKEN_LIFETIME', 300, 'int');
        $config->optional('SECURITY_COOKIE_DOMAIN', '', 'string');
        $config->optional('SECURITY_COOKIE_HTTPONLY', true, 'bool');
        $config->optional('SECURITY_COOKIE_SAMESITE', 'Strict', 'string');
        $config->optional('SECURITY_HTTP_SECURE', true, 'bool');
        $config->optional('APP_URL', '', 'string');
        // Comma-separated lists
        $config->optional('SECURITY_HTTP_RELAXED', '', 'string');
        $config->optional('SECURITY_CORS_ORIGINS', '', 'string');
    }

    public function getTokenLifetime(): int
    {
        return (int) $this->config->get('SECURITY_TOKEN_LIFETIME');
    }

    public function getCookieDomain(): string
    {
        return (string) $this->config->get('SECURITY_COOKIE_DOMAIN');
    }

    public function isCookieHttpOnly(): bool
    {
        return (bool) $this->config->get('SECURITY_COOKIE_HTTPONLY');
    }

    public function getCookieSameSite(): string
    {
        return (string) $this->config->get('SECURITY_COOKIE_SAMESITE');
    }

    public function isHttpSecure(): bool
    {
        return (bool) $this->config->get('SECURITY_HTTP_SECURE');
    }

    public function getAppUrl(): string
    {
        return (string) $this->config->get('APP_URL');
    }

    /** @return array<string> */
    public function getHttpRelaxedHosts(): array
    {
        $raw = (string) $this->config->get('SECURITY_HTTP_RELAXED');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }

    /** @return array<string> */
    public function getCorsAllowedOrigins(): array
    {
        $raw = (string) $this->config->get('SECURITY_CORS_ORIGINS');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }

    /** @return array<string, string> */
    public function getSecurityHeaderOverrides(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultSecurityConfig extends AbstractConfiguration implements SecurityConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_SECURITY_TOKEN_LIFETIME', 300, 'int');
        $this->optional('DIRECTIVE_SECURITY_COOKIE_DOMAIN', '', 'string');
        $this->optional('DIRECTIVE_SECURITY_COOKIE_HTTPONLY', true, 'bool');
        $this->optional('DIRECTIVE_SECURITY_COOKIE_SAMESITE', 'Strict', 'string');
        $this->optional('DIRECTIVE_SECURITY_HTTP_SECURE', true, 'bool');
        $this->optional('DIRECTIVE_APP_URL', '', 'string');
        // Comma-separated lists
        $this->optional('DIRECTIVE_SECURITY_HTTP_RELAXED', '', 'string');
        $this->optional('DIRECTIVE_SECURITY_CORS_ORIGINS', '', 'string');
    }

    public function getTokenLifetime(): int
    {
        return (int) $this->get('DIRECTIVE_SECURITY_TOKEN_LIFETIME');
    }

    public function getCookieDomain(): string
    {
        return (string) $this->get('DIRECTIVE_SECURITY_COOKIE_DOMAIN');
    }

    public function isCookieHttpOnly(): bool
    {
        return (bool) $this->get('DIRECTIVE_SECURITY_COOKIE_HTTPONLY');
    }

    public function getCookieSameSite(): string
    {
        return (string) $this->get('DIRECTIVE_SECURITY_COOKIE_SAMESITE');
    }

    public function isHttpSecure(): bool
    {
        return (bool) $this->get('DIRECTIVE_SECURITY_HTTP_SECURE');
    }

    public function getAppUrl(): string
    {
        return (string) $this->get('DIRECTIVE_APP_URL');
    }

    /** @return array<string> */
    public function getHttpRelaxedHosts(): array
    {
        $raw = (string) $this->get('DIRECTIVE_SECURITY_HTTP_RELAXED');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }

    /** @return array<string> */
    public function getCorsAllowedOrigins(): array
    {
        $raw = (string) $this->get('DIRECTIVE_SECURITY_CORS_ORIGINS');

        return $raw !== '' ? array_map('trim', explode(',', $raw)) : [];
    }

    /** @return array<string, string> */
    public function getSecurityHeaderOverrides(): array
    {
        return [];
    }
}

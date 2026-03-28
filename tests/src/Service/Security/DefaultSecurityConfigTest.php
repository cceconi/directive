<?php

declare(strict_types=1);

use Directive\Service\Security\DefaultSecurityConfig;
use Directive\Service\Security\SecurityConfigInterface;

describe('DefaultSecurityConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_SECURITY_TOKEN_LIFETIME'],
            $_ENV['DIRECTIVE_SECURITY_COOKIE_DOMAIN'],
            $_ENV['DIRECTIVE_SECURITY_COOKIE_HTTPONLY'],
            $_ENV['DIRECTIVE_SECURITY_COOKIE_SAMESITE'],
            $_ENV['DIRECTIVE_SECURITY_HTTP_SECURE'],
            $_ENV['DIRECTIVE_APP_URL'],
            $_ENV['DIRECTIVE_SECURITY_HTTP_RELAXED'],
            $_ENV['DIRECTIVE_SECURITY_CORS_ORIGINS'],
        );
    });

    it('implements SecurityConfigInterface', function (): void {
        $config = new DefaultSecurityConfig();
        $config->audit();
        expect($config)->toBeInstanceOf(SecurityConfigInterface::class);
    });

    it('returns defaults when no env vars are set', function (): void {
        $config = new DefaultSecurityConfig();
        $config->audit();

        expect($config->getTokenLifetime())->toBe(300);
        expect($config->getCookieDomain())->toBe('');
        expect($config->isCookieHttpOnly())->toBeTrue();
        expect($config->getCookieSameSite())->toBe('Strict');
        expect($config->isHttpSecure())->toBeTrue();
        expect($config->getAppUrl())->toBe('');
        expect($config->getHttpRelaxedHosts())->toBe([]);
        expect($config->getCorsAllowedOrigins())->toBe([]);
        expect($config->getSecurityHeaderOverrides())->toBe([]);
    });

    it('parses comma-separated relaxed hosts', function (): void {
        $_ENV['DIRECTIVE_SECURITY_HTTP_RELAXED'] = 'localhost, 127.0.0.1, dev.example.com';

        $config = new DefaultSecurityConfig();
        $config->audit();

        expect($config->getHttpRelaxedHosts())->toBe(['localhost', '127.0.0.1', 'dev.example.com']);
    });

    it('parses comma-separated CORS origins', function (): void {
        $_ENV['DIRECTIVE_SECURITY_CORS_ORIGINS'] = 'https://app.example.com, https://admin.example.com';

        $config = new DefaultSecurityConfig();
        $config->audit();

        expect($config->getCorsAllowedOrigins())->toBe(['https://app.example.com', 'https://admin.example.com']);
    });

    it('reads token lifetime from env', function (): void {
        $_ENV['DIRECTIVE_SECURITY_TOKEN_LIFETIME'] = '3600';

        $config = new DefaultSecurityConfig();
        $config->audit();

        expect($config->getTokenLifetime())->toBe(3600);
    });
});

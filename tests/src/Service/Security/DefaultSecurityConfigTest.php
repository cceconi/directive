<?php

declare(strict_types=1);

use Directive\Service\Security\DefaultSecurityConfig;
use Directive\Service\Security\SecurityConfigInterface;

describe('DefaultSecurityConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['SECURITY_TOKEN_LIFETIME'],
            $_ENV['SECURITY_COOKIE_DOMAIN'],
            $_ENV['SECURITY_COOKIE_HTTPONLY'],
            $_ENV['SECURITY_COOKIE_SAMESITE'],
            $_ENV['SECURITY_HTTP_SECURE'],
            $_ENV['APP_URL'],
            $_ENV['SECURITY_HTTP_RELAXED'],
            $_ENV['SECURITY_CORS_ORIGINS'],
        );
    });

    it('implements SecurityConfigInterface', function (): void {
        $config = makeTestConfig();    
        $securityConfig = new DefaultSecurityConfig($config);
    
        $securityConfig->define($config);
        $config->audit();
        expect($securityConfig)->toBeInstanceOf(SecurityConfigInterface::class);
    });

    it('returns defaults when no env vars are set', function (): void {
        $config = makeTestConfig();    
        $securityConfig = new DefaultSecurityConfig($config);
    
        $securityConfig->define($config);
        $config->audit();

        expect($securityConfig->getTokenLifetime())->toBe(300);
        expect($securityConfig->getCookieDomain())->toBe('');
        expect($securityConfig->isCookieHttpOnly())->toBeTrue();
        expect($securityConfig->getCookieSameSite())->toBe('Strict');
        expect($securityConfig->isHttpSecure())->toBeTrue();
        expect($securityConfig->getAppUrl())->toBe('');
        expect($securityConfig->getHttpRelaxedHosts())->toBe([]);
        expect($securityConfig->getCorsAllowedOrigins())->toBe([]);
        expect($securityConfig->getSecurityHeaderOverrides())->toBe([]);
    });

    it('parses comma-separated relaxed hosts', function (): void {
        $_ENV['SECURITY_HTTP_RELAXED'] = 'localhost, 127.0.0.1, dev.example.com';

        $config = makeTestConfig();
        $securityConfig = new DefaultSecurityConfig($config);

        $securityConfig->define($config);
        $config->audit();

        expect($securityConfig->getHttpRelaxedHosts())->toBe(['localhost', '127.0.0.1', 'dev.example.com']);
    });

    it('parses comma-separated CORS origins', function (): void {
        $_ENV['SECURITY_CORS_ORIGINS'] = 'https://app.example.com, https://admin.example.com';

        $config = makeTestConfig();
        $securityConfig = new DefaultSecurityConfig($config);

        $securityConfig->define($config);
        $config->audit();

        expect($securityConfig->getCorsAllowedOrigins())->toBe(['https://app.example.com', 'https://admin.example.com']);
    });

    it('reads token lifetime from env', function (): void {
        $_ENV['SECURITY_TOKEN_LIFETIME'] = '3600';

        $config = makeTestConfig();
        $securityConfig = new DefaultSecurityConfig($config);

        $securityConfig->define($config);
        $config->audit();

        expect($securityConfig->getTokenLifetime())->toBe(3600);
    });
});

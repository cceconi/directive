<?php

declare(strict_types=1);

use Directive\Http\Routing\RateLimitKeyType;
use Directive\Service\RateLimit\DefaultRateLimitConfig;

describe('DefaultRateLimitConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_RATE_LIMIT_REDIS_DSN'],
            $_ENV['DIRECTIVE_RATE_LIMIT_WINDOW'],
            $_ENV['DIRECTIVE_RATE_LIMIT_MAX_REQUESTS'],
            $_ENV['DIRECTIVE_RATE_LIMIT_KEY_TYPE'],
        );
    });

    it('returns default Redis DSN when env var is not set', function (): void {
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getRedisDsn())->toBe('tcp://127.0.0.1:6379');
    });

    it('returns custom Redis DSN from env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_REDIS_DSN'] = 'redis://myhost:6380';
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getRedisDsn())->toBe('redis://myhost:6380');
    });

    it('returns default window of 60 seconds', function (): void {
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultWindow())->toBe(60);
    });

    it('returns overridden window from env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_WINDOW'] = '300';
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultWindow())->toBe(300);
    });

    it('returns default max requests of 100', function (): void {
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultMaxRequests())->toBe(100);
    });

    it('returns overridden max requests from env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_MAX_REQUESTS'] = '50';
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultMaxRequests())->toBe(50);
    });

    it('returns default key type of Ip', function (): void {
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultKeyType())->toBe(RateLimitKeyType::Ip);
    });

    it('returns UserId key type from env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_KEY_TYPE'] = 'user_id';
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultKeyType())->toBe(RateLimitKeyType::UserId);
    });

    it('returns ApiKey key type from env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_KEY_TYPE'] = 'api_key';
        $cfg = new DefaultRateLimitConfig();
        $cfg->audit();
        expect($cfg->getDefaultKeyType())->toBe(RateLimitKeyType::ApiKey);
    });
});

<?php

declare(strict_types=1);

use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\AppIdentity\DefaultAppIdentityConfig;
use Tests\Helpers\TestConfig;

describe('DefaultAppIdentityConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['APP_CODE'],
            $_ENV['APP_NAME'],
            $_ENV['APP_VERSION'],
            $_ENV['APP_DESCRIPTION'],
            $_ENV['APP_URL'],
        );
    });

    it('implements AppIdentityConfigInterface', function (): void {
        $config = new TestConfig();
        $appIdentityConfig = new DefaultAppIdentityConfig($config);
        $config->audit();
        expect($appIdentityConfig)->toBeInstanceOf(AppIdentityConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new TestConfig();
        $appIdentityConfig = new DefaultAppIdentityConfig($config);
        $config->audit();

        expect($appIdentityConfig->getAppCode())->toBe('app');
        expect($appIdentityConfig->getAppName())->toBe('Directive App');
        expect($appIdentityConfig->getAppVersion())->toBe('1.0.0');
        expect($appIdentityConfig->getAppDescription())->toBe('');
        expect($appIdentityConfig->getAppUrl())->toBe('');
    });

    it('reads values from $_ENV', function (): void {
        $_ENV['APP_CODE']        = 'myapp';
        $_ENV['APP_NAME']        = 'My Application';
        $_ENV['APP_VERSION']     = '2.5.1';
        $_ENV['APP_DESCRIPTION'] = 'An amazing app';
        $_ENV['APP_URL']         = 'https://example.com';

        $config = new TestConfig();
        $appIdentityConfig = new DefaultAppIdentityConfig($config);
        $config->audit();

        expect($appIdentityConfig->getAppCode())->toBe('myapp');
        expect($appIdentityConfig->getAppName())->toBe('My Application');
        expect($appIdentityConfig->getAppVersion())->toBe('2.5.1');
        expect($appIdentityConfig->getAppDescription())->toBe('An amazing app');
        expect($appIdentityConfig->getAppUrl())->toBe('https://example.com');
    });
});

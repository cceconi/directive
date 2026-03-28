<?php

declare(strict_types=1);

use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\AppIdentity\DefaultAppIdentityConfig;

describe('DefaultAppIdentityConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_APP_CODE'],
            $_ENV['DIRECTIVE_APP_NAME'],
            $_ENV['DIRECTIVE_APP_VERSION'],
            $_ENV['DIRECTIVE_APP_DESCRIPTION'],
            $_ENV['DIRECTIVE_APP_URL'],
        );
    });

    it('implements AppIdentityConfigInterface', function (): void {
        $config = new DefaultAppIdentityConfig();
        $config->audit();
        expect($config)->toBeInstanceOf(AppIdentityConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new DefaultAppIdentityConfig();
        $config->audit();

        expect($config->getAppCode())->toBe('app');
        expect($config->getAppName())->toBe('Directive App');
        expect($config->getAppVersion())->toBe('1.0.0');
        expect($config->getAppDescription())->toBe('');
        expect($config->getAppUrl())->toBe('');
    });

    it('reads values from $_ENV', function (): void {
        $_ENV['DIRECTIVE_APP_CODE']        = 'myapp';
        $_ENV['DIRECTIVE_APP_NAME']        = 'My Application';
        $_ENV['DIRECTIVE_APP_VERSION']     = '2.5.1';
        $_ENV['DIRECTIVE_APP_DESCRIPTION'] = 'An amazing app';
        $_ENV['DIRECTIVE_APP_URL']         = 'https://example.com';

        $config = new DefaultAppIdentityConfig();
        $config->audit();

        expect($config->getAppCode())->toBe('myapp');
        expect($config->getAppName())->toBe('My Application');
        expect($config->getAppVersion())->toBe('2.5.1');
        expect($config->getAppDescription())->toBe('An amazing app');
        expect($config->getAppUrl())->toBe('https://example.com');
    });
});

<?php

declare(strict_types=1);

use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\AppIdentity\DefaultAppIdentityConfig;
use Directive\Service\AppManagement\AppInfo;

describe('DefaultAppIdentityConfig', function (): void {

    beforeEach(function (): void {
        unset($_ENV['APP_ENV'], $_ENV['APP_DESCRIPTION'], $_ENV['APP_URL']);
    });

    afterEach(function (): void {
        unset($_ENV['APP_ENV'], $_ENV['APP_DESCRIPTION'], $_ENV['APP_URL']);
    });

    it('implements AppIdentityConfigInterface', function (): void {
        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo(['name' => 'my-app', 'version' => '1.0.0']), $config);
        $subject->define($config);
        $config->audit();
        expect($subject)->toBeInstanceOf(AppIdentityConfigInterface::class);
    });

    it('reads name and version from AppInfo', function (): void {
        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo(['name' => 'My App', 'version' => '2.5.1']), $config);
        $subject->define($config);
        $config->audit();

        expect($subject->getAppName())->toBe('My App');
        expect($subject->getAppVersion())->toBe('2.5.1');
    });

    it('derives slug code from AppInfo name', function (): void {
        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo(['name' => 'My Test Project']), $config);
        $subject->define($config);
        $config->audit();

        expect($subject->getAppCode())->toBe('my-test-project');
    });

    it('returns empty string when AppInfo is empty', function (): void {
        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo([]), $config);
        $subject->define($config);
        $config->audit();

        expect($subject->getAppName())->toBe('');
        expect($subject->getAppVersion())->toBe('');
        expect($subject->getAppCode())->toBe('');
    });

    it('reads env and description from configuration', function (): void {
        $_ENV['APP_ENV']         = 'staging';
        $_ENV['APP_DESCRIPTION'] = 'A staging app';
        $_ENV['APP_URL']         = 'https://staging.example.com';

        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo(['name' => 'app']), $config);
        $subject->define($config);
        $config->audit();

        expect($subject->getAppEnv())->toBe('staging');
        expect($subject->getAppDescription())->toBe('A staging app');
        expect($subject->getAppUrl())->toBe('https://staging.example.com');
    });

    it('collapses multiple separators into single dash in code', function (): void {
        $config = makeTestConfig();
        $subject = new DefaultAppIdentityConfig(new AppInfo(['name' => 'My -- Cool  App!']), $config);
        $subject->define($config);
        $config->audit();

        expect($subject->getAppCode())->toBe('my-cool-app');
    });
});

<?php

declare(strict_types=1);

use Directive\Service\Security\Antivirus\AntivirusConfigInterface;
use Directive\Service\Security\Antivirus\DefaultAntivirusConfig;
use Tests\Helpers\TestConfig;

describe('DefaultAntivirusConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['ANTIVIRUS_NAME'],
            $_ENV['ANTIVIRUS_HOST'],
            $_ENV['ANTIVIRUS_PORT'],
            $_ENV['ANTIVIRUS_TIMEOUT'],
        );
    });

    it('implements AntivirusConfigInterface', function (): void {
        $config = new TestConfig();
        $antivirusConfig = new DefaultAntivirusConfig($config);
        $config->audit();
        expect($antivirusConfig)->toBeInstanceOf(AntivirusConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new TestConfig();
        $antivirusConfig = new DefaultAntivirusConfig($config);
        $config->audit();

        expect($antivirusConfig->getName())->toBe('clamav');
        expect($antivirusConfig->getHost())->toBe('127.0.0.1');
        expect($antivirusConfig->getPort())->toBe(3310);
        expect($antivirusConfig->getTimeout())->toBe(5);
    });

    it('casts port to int from env', function (): void {
        $_ENV['ANTIVIRUS_PORT'] = '9999';

        $config = new TestConfig();
        $antivirusConfig = new DefaultAntivirusConfig($config);
        $config->audit();

        expect($antivirusConfig->getPort())->toBe(9999);
        expect($antivirusConfig->getPort())->toBeInt();
    });

    it('casts timeout to int from env', function (): void {
        $_ENV['ANTIVIRUS_TIMEOUT'] = '30';

        $config = new TestConfig();
        $antivirusConfig = new DefaultAntivirusConfig($config);
        $config->audit();

        expect($antivirusConfig->getTimeout())->toBe(30);
        expect($antivirusConfig->getTimeout())->toBeInt();
    });

    it('reads host from env', function (): void {
        $_ENV['ANTIVIRUS_HOST'] = '10.0.0.1';

        $config = new TestConfig();
        $antivirusConfig = new DefaultAntivirusConfig($config);
        $config->audit();

        expect($antivirusConfig->getHost())->toBe('10.0.0.1');
    });
});

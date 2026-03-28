<?php

declare(strict_types=1);

use Directive\Service\Security\Antivirus\AntivirusConfigInterface;
use Directive\Service\Security\Antivirus\DefaultAntivirusConfig;

describe('DefaultAntivirusConfig', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_ANTIVIRUS_NAME'],
            $_ENV['DIRECTIVE_ANTIVIRUS_HOST'],
            $_ENV['DIRECTIVE_ANTIVIRUS_PORT'],
            $_ENV['DIRECTIVE_ANTIVIRUS_TIMEOUT'],
        );
    });

    it('implements AntivirusConfigInterface', function (): void {
        $config = new DefaultAntivirusConfig();
        $config->audit();
        expect($config)->toBeInstanceOf(AntivirusConfigInterface::class);
    });

    it('returns default values when no env vars are set', function (): void {
        $config = new DefaultAntivirusConfig();
        $config->audit();

        expect($config->getName())->toBe('clamav');
        expect($config->getHost())->toBe('127.0.0.1');
        expect($config->getPort())->toBe(3310);
        expect($config->getTimeout())->toBe(5);
    });

    it('casts port to int from env', function (): void {
        $_ENV['DIRECTIVE_ANTIVIRUS_PORT'] = '9999';

        $config = new DefaultAntivirusConfig();
        $config->audit();

        expect($config->getPort())->toBe(9999);
        expect($config->getPort())->toBeInt();
    });

    it('casts timeout to int from env', function (): void {
        $_ENV['DIRECTIVE_ANTIVIRUS_TIMEOUT'] = '30';

        $config = new DefaultAntivirusConfig();
        $config->audit();

        expect($config->getTimeout())->toBe(30);
        expect($config->getTimeout())->toBeInt();
    });

    it('reads host from env', function (): void {
        $_ENV['DIRECTIVE_ANTIVIRUS_HOST'] = '10.0.0.1';

        $config = new DefaultAntivirusConfig();
        $config->audit();

        expect($config->getHost())->toBe('10.0.0.1');
    });
});

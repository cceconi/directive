<?php

declare(strict_types=1);

use Directive\Exception\ConfigurationException;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Configuration\ConfigSourceTracker;

// ── Stub ─────────────────────────────────────────────────────────────────────

final class SourceTrackingConfig extends AbstractConfiguration
{
    protected function define(): void
    {
        $this->required('APP_ENV', 'string');
        $this->optional('CACHE_TTL', 300, 'int');
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('AbstractConfiguration source tracking', function (): void {

    beforeEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['CACHE_TTL']);
    });

    afterEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['CACHE_TTL']);
    });

    it('getSource() returns "default" for optional keys absent from $_ENV', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $config = new SourceTrackingConfig();
        $config->audit();

        expect($config->getSource('CACHE_TTL'))->toBe('default');
    });

    it('getSource() returns tracker value for resolved keys', function (): void {
        // Unset APP_ENV so loadTracked can introduce it (symfony/dotenv load() does not overwrite existing vars)
        unset($_ENV['APP_ENV']);

        $tmpDir = sys_get_temp_dir() . '/directive-source-test-' . uniqid();
        mkdir($tmpDir, 0755, true);
        file_put_contents($tmpDir . '/.env.local', "APP_ENV=production\n");

        $dotenv = new Symfony\Component\Dotenv\Dotenv();
        ConfigSourceTracker::loadTracked($dotenv, $tmpDir);

        @unlink($tmpDir . '/.env.local');
        @rmdir($tmpDir);

        $config = new SourceTrackingConfig();
        $config->audit();

        expect($config->getSource('APP_ENV'))->toBe('.env.local');
    });

    it('getSource() returns "default" when tracker has no entry (no loadTracked called)', function (): void {
        $_ENV['APP_ENV'] = 'staging';
        // ConfigSourceTracker is reset (from beforeEach), so getSource returns null → audit uses 'default'? No:
        // Actually, 'default' is only used when raw === null (var not in $_ENV).
        // When raw !== null and tracker returns null, source is ConfigSourceTracker::getSource() ?? 'default'
        // → since tracker is empty, result is 'default'

        $config = new SourceTrackingConfig();
        $config->audit();

        // Tracker has no entry for APP_ENV → source = 'default' (fallback)
        expect($config->getSource('APP_ENV'))->toBe('default');
    });

    it('getSource() throws ConfigurationException for undeclared key', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $config = new SourceTrackingConfig();
        $config->audit();

        expect(fn () => $config->getSource('UNDECLARED'))->toThrow(ConfigurationException::class);
    });

    it('getSource() throws ConfigurationException before audit() is called', function (): void {
        $config = new SourceTrackingConfig();

        expect(fn () => $config->getSource('APP_ENV'))->toThrow(ConfigurationException::class);
    });

    it('getAll() still returns correct values after source tracking is added', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['CACHE_TTL'] = '600';

        $config = new SourceTrackingConfig();
        $config->audit();

        expect($config->getAll())->toMatchArray([
            'APP_ENV'   => 'production',
            'CACHE_TTL' => 600,
        ]);
    });
});

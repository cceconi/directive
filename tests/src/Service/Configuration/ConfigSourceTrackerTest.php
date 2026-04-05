<?php

declare(strict_types=1);

use Directive\Service\Configuration\ConfigSourceTracker;
use Symfony\Component\Dotenv\Dotenv;

describe('ConfigSourceTracker', function (): void {

    beforeEach(function (): void {
        ConfigSourceTracker::reset();
    });

    afterEach(function (): void {
        ConfigSourceTracker::reset();
    });

    it('returns null for an untracked key', function (): void {
        expect(ConfigSourceTracker::getSource('UNKNOWN_VAR_TEST'))->toBeNull();
    });

    it('snapshotSystemVars() marks existing $_ENV keys as "system"', function (): void {
        $_ENV['TRACKER_SYS'] = 'value';

        ConfigSourceTracker::snapshotSystemVars();

        expect(ConfigSourceTracker::getSource('TRACKER_SYS'))->toBe('system');

        unset($_ENV['TRACKER_SYS']);
    });

    it('snapshotSystemVars() does not overwrite already-tracked keys', function (): void {
        // Ensure var is NOT in $_ENV so loadTracked introduces it (symfony/dotenv load() skips existing vars)
        unset($_ENV['TRACKER_PRE']);

        $tmpDir = sys_get_temp_dir() . '/directive-tracker-pre-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        file_put_contents($tmpDir . '/.env', "TRACKER_PRE=from-env\n");

        $dotenv = new Dotenv();
        ConfigSourceTracker::loadTracked($dotenv, $tmpDir);

        // loadTracked recorded it as '.env'; snapshotSystemVars should NOT overwrite that
        ConfigSourceTracker::snapshotSystemVars();

        expect(ConfigSourceTracker::getSource('TRACKER_PRE'))->toBe('.env');

        @unlink($tmpDir . '/.env');
        @rmdir($tmpDir);
        unset($_ENV['TRACKER_PRE']);
    });

    it('loadTracked() tracks variables from .env as ".env"', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-tracker-env-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        file_put_contents($tmpDir . '/.env', "FROM_ENV=hello\n");

        $dotenv = new Dotenv();
        ConfigSourceTracker::loadTracked($dotenv, $tmpDir);

        expect(ConfigSourceTracker::getSource('FROM_ENV'))->toBe('.env');

        @unlink($tmpDir . '/.env');
        @rmdir($tmpDir);
        unset($_ENV['FROM_ENV']);
    });

    it('loadTracked() tracks variables from .env.local as ".env.local"', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-tracker-local-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        file_put_contents($tmpDir . '/.env.local', "FROM_LOCAL=world\n");

        $dotenv = new Dotenv();
        ConfigSourceTracker::loadTracked($dotenv, $tmpDir);

        expect(ConfigSourceTracker::getSource('FROM_LOCAL'))->toBe('.env.local');

        @unlink($tmpDir . '/.env.local');
        @rmdir($tmpDir);
        unset($_ENV['FROM_LOCAL']);
    });

    it('loadTracked() skips files that do not exist (no error)', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-tracker-nofiles-' . uniqid();
        mkdir($tmpDir, 0o755, true);

        $dotenv = new Dotenv();

        // Should not throw
        ConfigSourceTracker::loadTracked($dotenv, $tmpDir);

        @rmdir($tmpDir);

        expect(true)->toBeTrue();
    });

    it('reset() clears all tracked entries', function (): void {
        $_ENV['RESET_TEST'] = 'yes';
        ConfigSourceTracker::snapshotSystemVars();

        expect(ConfigSourceTracker::getSource('RESET_TEST'))->toBe('system');

        ConfigSourceTracker::reset();

        expect(ConfigSourceTracker::getSource('RESET_TEST'))->toBeNull();

        unset($_ENV['RESET_TEST']);
    });
});

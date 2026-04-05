<?php

declare(strict_types=1);

use Directive\Console\ConfigAuditCommand;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigSourceTracker;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeAuditConfig(): Configuration
{
    $config = new Configuration();
    $config->required('APP_ENV', 'string');
    $config->optional('APP_SECRET_KEY', 'secret-default', 'string');
    $config->optional('CACHE_TTL', 300, 'int');
    return $config;
}

function makeAuditContainer(bool $hasConfig = true): ContainerInterface
{
    return new class ($hasConfig) implements ContainerInterface {
        public function __construct(private bool $has) {}

        public function get(string $id): mixed
        {
            return $this->has ? makeAuditConfig() : null;
        }

        public function has(string $id): bool
        {
            return $this->has && $id === Configuration::class;
        }
    };
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('ConfigAuditCommand', function (): void {

    beforeEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['APP_SECRET_KEY'], $_ENV['CACHE_TTL']);
    });

    afterEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['APP_SECRET_KEY'], $_ENV['CACHE_TTL']);
    });

    it('displays a table with variable names', function (): void {
        $_ENV['APP_ENV'] = 'prod';

        $command = new ConfigAuditCommand(makeAuditContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('APP_ENV');
        expect($tester->getDisplay())->toContain('CACHE_TTL');
    });

    it('masks sensitive variable values as ***', function (): void {
        $_ENV['APP_ENV']        = 'prod';
        $_ENV['APP_SECRET_KEY'] = 'super-secret-value';

        $command = new ConfigAuditCommand(makeAuditContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        expect($output)->not->toContain('super-secret-value');
        expect($output)->toContain('***');
    });

    it('shows resolved value for non-sensitive variable', function (): void {
        $_ENV['APP_ENV']  = 'prod';
        $_ENV['CACHE_TTL'] = '600';

        $command = new ConfigAuditCommand(makeAuditContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        expect($tester->getDisplay())->toContain('600');
    });

    it('shows source column in output', function (): void {
        $_ENV['APP_ENV'] = 'test';

        $command = new ConfigAuditCommand(makeAuditContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        $output = $tester->getDisplay();
        // Source column header must appear
        expect($output)->toContain('Source');
    });

    it('exits successfully with message when no config bound', function (): void {
        $command = new ConfigAuditCommand(makeAuditContainer(false));
        $tester  = new CommandTester($command);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('No Configuration');
    });
});

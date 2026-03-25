<?php

declare(strict_types=1);

use Directive\Console\ConfigVerifyCommand;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Configuration\ConfigurationVaultInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeVerifyConfig(): AbstractConfiguration
{
    return new class extends AbstractConfiguration {
        protected function define(): void
        {
            $this->required('APP_ENV', 'string');
            $this->required('DB_PORT', 'int');
        }
        public function audit(): void {} // no-op
    };
}

function makeVault(array $keys): ConfigurationVaultInterface
{
    return new class($keys) implements ConfigurationVaultInterface {
        public function __construct(private readonly array $keys) {}
        public function getKeys(): array { return $this->keys; }
    };
}

function makeVerifyContainer(bool $hasConfig, bool $hasVault, array $vaultKeys = []): ContainerInterface
{
    return new class($hasConfig, $hasVault, $vaultKeys) implements ContainerInterface {
        public function __construct(
            private readonly bool $hasConfig,
            private readonly bool $hasVault,
            private readonly array $vaultKeys,
        ) {}

        public function get(string $id): mixed
        {
            if ($id === AbstractConfiguration::class) {
                return makeVerifyConfig();
            }
            return makeVault($this->vaultKeys);
        }

        public function has(string $id): bool
        {
            if ($id === AbstractConfiguration::class) {
                return $this->hasConfig;
            }
            if ($id === ConfigurationVaultInterface::class) {
                return $this->hasVault;
            }
            return false;
        }
    };
}

describe('ConfigVerifyCommand', function (): void {

    it('exits 0 when config and vault keys are in sync', function (): void {
        $command = new ConfigVerifyCommand(makeVerifyContainer(true, true, ['APP_ENV', 'DB_PORT']));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('in sync');
    });

    it('exits 1 and reports key missing from vault', function (): void {
        // Vault has only APP_ENV, missing DB_PORT
        $command = new ConfigVerifyCommand(makeVerifyContainer(true, true, ['APP_ENV']));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(1);
        expect($tester->getDisplay())->toContain('DB_PORT');
    });

    it('exits 1 and reports vault key not declared in config', function (): void {
        // Vault has an extra key EXTRA_KEY not in config
        $command = new ConfigVerifyCommand(makeVerifyContainer(true, true, ['APP_ENV', 'DB_PORT', 'EXTRA_KEY']));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(1);
        expect($tester->getDisplay())->toContain('EXTRA_KEY');
    });

    it('succeeds gracefully when no AbstractConfiguration is bound', function (): void {
        $command = new ConfigVerifyCommand(makeVerifyContainer(false, false));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
    });

    it('succeeds gracefully when no vault is bound', function (): void {
        $command = new ConfigVerifyCommand(makeVerifyContainer(true, false));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
    });
});

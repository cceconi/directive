<?php

declare(strict_types=1);

use Directive\Console\ConfigCheckCommand;
use Directive\Service\Configuration\Configuration;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeConfig(bool $throws = false): Configuration
{
    $config = new Configuration();
    if ($throws) {
        // Required key absent from $_ENV → audit() will throw ConfigurationException
        unset($_ENV['__DIRECTIVE_TEST_REQUIRED__']);
        $config->required('__DIRECTIVE_TEST_REQUIRED__', 'string');
    }
    return $config;
}

function makeContainer(bool $hasConfig, bool $configThrows = false): ContainerInterface
{
    return new class ($hasConfig, $configThrows) implements ContainerInterface {
        public function __construct(
            private readonly bool $hasConfig,
            private readonly bool $configThrows,
        ) {}
        public function get(string $id): mixed
        {
            return makeConfig($this->configThrows);
        }
        public function has(string $id): bool
        {
            return $this->hasConfig && $id === Configuration::class;
        }
    };
}

describe('ConfigCheckCommand', function (): void {

    it('outputs success when audit passes', function (): void {
        $command = new ConfigCheckCommand(makeContainer(true, false));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('valid');
    });

    it('outputs failure when audit throws', function (): void {
        $command = new ConfigCheckCommand(makeContainer(true, true));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(1);
        expect($tester->getDisplay())->toContain('errors detected');
    });

    it('succeeds gracefully when no Configuration is bound', function (): void {
        $command = new ConfigCheckCommand(makeContainer(false));
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
    });
});

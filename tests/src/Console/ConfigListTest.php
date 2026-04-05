<?php

declare(strict_types=1);

use Directive\Console\ConfigListCommand;
use Directive\Service\Configuration\Configuration;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeListConfig(): Configuration
{
    $config = new Configuration();
    $config->optional('APP_ENV', 'prod', 'string');
    $config->optional('APP_SECRET', '***', 'string');
    return $config;
}

function makeListContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return makeListConfig();
        }
        public function has(string $id): bool
        {
            return $id === Configuration::class;
        }
    };
}

describe('ConfigListCommand', function (): void {

    it('renders a table with declared keys', function (): void {
        $command = new ConfigListCommand(makeListContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('APP_ENV');
    });

    it('masks sensitive keys', function (): void {
        $command = new ConfigListCommand(makeListContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);
        $display = $tester->getDisplay();
        // APP_SECRET contains SECRET → should be masked
        expect($display)->toContain('***');
    });
});

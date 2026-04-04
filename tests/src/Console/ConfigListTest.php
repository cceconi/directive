<?php

declare(strict_types=1);

use Directive\Console\ConfigListCommand;
use Directive\Service\Configuration\AbstractConfiguration;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeListConfig(): AbstractConfiguration
{
    return new class extends AbstractConfiguration {
        protected function define(): void
        {
            $this->required('APP_ENV', 'string');
            $this->optional('APP_SECRET', '***', 'string');
        }
        public function audit(): void
        {
            // no-op for tests
        }
    };
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
            return $id === AbstractConfiguration::class;
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

<?php

declare(strict_types=1);

use Directive\Console\ConfigExportCommand;
use Directive\Service\Configuration\AbstractConfiguration;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeExportConfig(): AbstractConfiguration
{
    return new class extends AbstractConfiguration {
        protected function define(): void
        {
            $this->required('APP_ENV', 'string');
            $this->required('DB_PORT', 'int');
            $this->optional('LOG_LEVEL', 'info', 'string');
        }
        public function audit(): void {} // no-op
    };
}

function makeExportContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return makeExportConfig();
        }
        public function has(string $id): bool
        {
            return $id === AbstractConfiguration::class;
        }
    };
}

describe('ConfigExportCommand', function (): void {

    it('generates a .env.example with empty values', function (): void {
        $tmpFile = sys_get_temp_dir() . '/test-export-' . uniqid() . '.env.example';
        $command = new ConfigExportCommand(makeExportContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $tmpFile]);

        expect($tester->getStatusCode())->toBe(0);

        $content = (string) file_get_contents($tmpFile);
        expect($content)->toContain('APP_ENV=');
        expect($content)->toContain('DB_PORT=');
        expect($content)->toContain('LOG_LEVEL=');

        unlink($tmpFile);
    });

    it('does not write current $_ENV values to the file', function (): void {
        $_ENV['APP_ENV'] = 'production-secret-value';
        $_ENV['DB_PORT'] = '54321';

        $tmpFile = sys_get_temp_dir() . '/test-export-' . uniqid() . '.env.example';
        $command = new ConfigExportCommand(makeExportContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $tmpFile]);

        $content = (string) file_get_contents($tmpFile);
        expect($content)->not->toContain('production-secret-value');
        expect($content)->not->toContain('54321');

        unset($_ENV['APP_ENV'], $_ENV['DB_PORT']);
        unlink($tmpFile);
    });

    it('adds a comment with type and required/optional status', function (): void {
        $tmpFile = sys_get_temp_dir() . '/test-export-' . uniqid() . '.env.example';
        $command = new ConfigExportCommand(makeExportContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $tmpFile]);

        $content = (string) file_get_contents($tmpFile);
        expect($content)->toContain('# required | type: string');
        expect($content)->toContain('# optional | type: string');

        unlink($tmpFile);
    });

    it('succeeds gracefully when no AbstractConfiguration is bound', function (): void {
        $container = new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                return null;
            }
            public function has(string $id): bool
            {
                return false;
            }
        };
        $command = new ConfigExportCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
    });
});

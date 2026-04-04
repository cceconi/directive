<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateUidCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeUidContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return null;
        }
        public function has(string $id): bool
        {
            return false;
        }
    };
}

function makeUidTester(): CommandTester
{
    $command = new GenerateUidCommand(makeUidContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:uid'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-uid-' . uniqid();
    mkdir($this->cwd);
    file_put_contents(
        $this->cwd . '/directive-dev.json',
        json_encode(['namespace' => 'App', 'src' => $this->cwd . '/src']),
    );
    chdir($this->cwd);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->cwd, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }
    rmdir($this->cwd);
});

describe('GenerateUidCommand', function (): void {

    it('generates EntityNameId.php in Model/', function (): void {
        $tester = makeUidTester();
        $tester->execute(['EntityName' => 'Order']);

        $file = $this->cwd . '/src/Model/OrderId.php';
        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($file))->toBeTrue();
    });

    it('generated class extends AbstractUid', function (): void {
        $tester = makeUidTester();
        $tester->execute(['EntityName' => 'Customer']);

        $content = (string) file_get_contents($this->cwd . '/src/Model/CustomerId.php');
        expect($content)->toContain('extends AbstractUid');
    });

    it('generated class has a static generate() factory using Uuid::uuid7', function (): void {
        $tester = makeUidTester();
        $tester->execute(['EntityName' => 'Order']);

        $content = (string) file_get_contents($this->cwd . '/src/Model/OrderId.php');
        expect($content)
            ->toContain('public static function generate(): self')
            ->toContain('Uuid::uuid7()');
    });

    it('accepts entity name via interactive mode', function (): void {
        $tester = makeUidTester();
        $tester->setInputs(['Product']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/Model/ProductId.php'))->toBeTrue();
    });

    it('warns when composer.json has multiple PSR-4 mappings', function (): void {
        // Remove directive-dev.json so resolver falls back to composer.json
        @unlink($this->cwd . '/directive-dev.json');

        file_put_contents(
            $this->cwd . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['App\\' => 'src/', 'Lib\\' => 'lib/']]]),
        );

        $tester = makeUidTester();
        $tester->execute(['EntityName' => 'Widget']);

        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('Multiple PSR-4 autoload mappings');
    });

});

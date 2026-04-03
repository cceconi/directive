<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateRepositoryInterfaceCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeRepoContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed { return null; }
        public function has(string $id): bool  { return false; }
    };
}

function makeRepoTester(): CommandTester
{
    $command = new GenerateRepositoryInterfaceCommand(makeRepoContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:repository-interface'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-repo-' . uniqid();
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

describe('GenerateRepositoryInterfaceCommand', function (): void {

    it('generates the interface file in Spi/', function (): void {
        $tester = makeRepoTester();
        $tester->execute(['Entity' => 'Order']);

        $file = $this->cwd . '/src/Spi/OrderRepositoryInterface.php';
        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($file))->toBeTrue();
    });

    it('generated interface has the 4 required methods', function (): void {
        $tester = makeRepoTester();
        $tester->execute(['Entity' => 'Product']);

        $content = (string) file_get_contents($this->cwd . '/src/Spi/ProductRepositoryInterface.php');
        expect($content)
            ->toContain('public function findById(')
            ->toContain('public function findAll(')
            ->toContain('public function save(')
            ->toContain('public function delete(');
    });

    it('findAll has @return array<int, Entity> PHPDoc', function (): void {
        $tester = makeRepoTester();
        $tester->execute(['Entity' => 'Customer']);

        $content = (string) file_get_contents($this->cwd . '/src/Spi/CustomerRepositoryInterface.php');
        expect($content)->toContain('@return array<int, Customer>');
    });

    it('accepts entity name via interactive mode', function (): void {
        $tester = makeRepoTester();
        $tester->setInputs(['Invoice']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/Spi/InvoiceRepositoryInterface.php'))->toBeTrue();
    });

});

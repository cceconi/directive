<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateUseCaseCommandCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeUcCommandContainer(): ContainerInterface
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

function makeUseCaseCommandTester(): CommandTester
{
    $command = new GenerateUseCaseCommandCommand(makeUcCommandContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:usecase-command'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-ucmd-' . uniqid();
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

describe('GenerateUseCaseCommandCommand', function (): void {

    it('generates 5 files for a command UseCase', function (): void {
        $tester = makeUseCaseCommandTester();
        $tester->execute(['Business' => 'Order', 'UseCaseName' => 'CreateOrder']);

        expect($tester->getStatusCode())->toBe(0);

        $src = $this->cwd . '/src';
        expect(file_exists($src . '/UseCase/Order/CreateOrderUseCase.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/CreateOrderCommand.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/CreateOrderPayload.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/CreateOrderResult.php'))->toBeTrue();
        expect(file_exists($src . '/Api/Order/CreateOrderUseCaseInterface.php'))->toBeTrue();
    });

    it('generated UseCase references Command input type', function (): void {
        $tester = makeUseCaseCommandTester();
        $tester->execute(['Business' => 'Order', 'UseCaseName' => 'CreateOrder']);

        $content = (string) file_get_contents($this->cwd . '/src/UseCase/Order/CreateOrderUseCase.php');
        expect($content)
            ->toContain('CreateOrderCommand')
            ->not->toContain('CreateOrderQuery');
    });

    it('creates missing directories', function (): void {
        $tester = makeUseCaseCommandTester();
        $tester->execute(['Business' => 'Billing', 'UseCaseName' => 'ProcessPayment']);

        expect(is_dir($this->cwd . '/src/UseCase/Billing'))->toBeTrue();
        expect(is_dir($this->cwd . '/src/Api/Billing'))->toBeTrue();
    });

    it('accepts arguments via interactive mode', function (): void {
        $tester = makeUseCaseCommandTester();
        $tester->setInputs(['Shipping', 'SendPackage']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/UseCase/Shipping/SendPackageUseCase.php'))->toBeTrue();
    });

});

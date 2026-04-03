<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateUseCaseQueryCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeUcQueryContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed { return null; }
        public function has(string $id): bool  { return false; }
    };
}

function makeUseCaseQueryTester(): CommandTester
{
    $command = new GenerateUseCaseQueryCommand(makeUcQueryContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:usecase-query'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-ucqry-' . uniqid();
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

describe('GenerateUseCaseQueryCommand', function (): void {

    it('generates 5 files for a query UseCase', function (): void {
        $tester = makeUseCaseQueryTester();
        $tester->execute(['Business' => 'Order', 'QueryName' => 'GetOrderById']);

        expect($tester->getStatusCode())->toBe(0);

        $src = $this->cwd . '/src';
        expect(file_exists($src . '/UseCase/Order/GetOrderByIdUseCase.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/GetOrderByIdQuery.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/GetOrderByIdPayload.php'))->toBeTrue();
        expect(file_exists($src . '/UseCase/Order/GetOrderByIdResult.php'))->toBeTrue();
        expect(file_exists($src . '/Api/Order/GetOrderByIdUseCaseInterface.php'))->toBeTrue();
    });

    it('generated UseCase references Query input type', function (): void {
        $tester = makeUseCaseQueryTester();
        $tester->execute(['Business' => 'Order', 'QueryName' => 'GetOrderById']);

        $content = (string) file_get_contents($this->cwd . '/src/UseCase/Order/GetOrderByIdUseCase.php');
        expect($content)
            ->toContain('GetOrderByIdQuery')
            ->not->toContain('GetOrderByIdCommand');
    });

    it('accepts arguments via interactive mode', function (): void {
        $tester = makeUseCaseQueryTester();
        $tester->setInputs(['Catalog', 'ListProducts']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/UseCase/Catalog/ListProductsUseCase.php'))->toBeTrue();
    });

});

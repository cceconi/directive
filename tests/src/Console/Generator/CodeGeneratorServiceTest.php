<?php

declare(strict_types=1);

use Directive\Console\Generator\ClassType;
use Directive\Console\Generator\CodeGeneratorService;

beforeEach(function (): void {
    $this->tmpDir = sys_get_temp_dir() . '/directive-gen-test-' . uniqid();
    mkdir($this->tmpDir);
    $this->generator = new CodeGeneratorService();
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->tmpDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }
    rmdir($this->tmpDir);
});

describe('CodeGeneratorService', function (): void {

    it('generates a UseCase file with correct namespace and class name', function (): void {
        $dir = $this->tmpDir . '/UseCase/Order';
        $this->generator->generate('Order', 'CreateOrder', $dir, 'App', ClassType::USE_CASE->value, ['input' => 'Command']);

        $file = $dir . '/CreateOrderUseCase.php';
        expect(file_exists($file))->toBeTrue();
        $content = (string) file_get_contents($file);
        expect($content)
            ->toContain('namespace App\\UseCase\\Order')
            ->toContain('class CreateOrderUseCase extends AbstractUseCase')
            ->toContain('CreateOrderCommand');
    });

    it('generates a Command file', function (): void {
        $dir = $this->tmpDir . '/UseCase/Order';
        $this->generator->generate('Order', 'CreateOrder', $dir, 'App', ClassType::COMMAND->value);

        $file = $dir . '/CreateOrderCommand.php';
        expect(file_exists($file))->toBeTrue();
        $content = (string) file_get_contents($file);
        expect($content)
            ->toContain('class CreateOrderCommand extends AbstractCommand');
    });

    it('generates a Query file', function (): void {
        $dir = $this->tmpDir . '/UseCase/Order';
        $this->generator->generate('Order', 'GetOrder', $dir, 'App', ClassType::QUERY->value);

        $file = $dir . '/GetOrderQuery.php';
        expect(file_exists($file))->toBeTrue();
        $content = (string) file_get_contents($file);
        expect($content)->toContain('class GetOrderQuery extends AbstractQuery');
    });

    it('generates a Result file with presentData()', function (): void {
        $dir = $this->tmpDir . '/UseCase/Order';
        $this->generator->generate('Order', 'CreateOrder', $dir, 'App', ClassType::RESULT->value);

        $content = (string) file_get_contents($dir . '/CreateOrderResult.php');
        expect($content)
            ->toContain('class CreateOrderResult extends AbstractResult')
            ->toContain('presentData');
    });

    it('generates a UseCaseInterface file', function (): void {
        $dir = $this->tmpDir . '/Api/Order';
        $this->generator->generate('Order', 'CreateOrder', $dir, 'App', ClassType::USE_CASE_INTERFACE->value);

        $content = (string) file_get_contents($dir . '/CreateOrderUseCaseInterface.php');
        expect($content)
            ->toContain('interface CreateOrderUseCaseInterface extends UseCaseInterface');
    });

    it('does not overwrite an existing file', function (): void {
        $dir  = $this->tmpDir . '/UseCase/Order';
        mkdir($dir, 0o775, true);
        file_put_contents($dir . '/CreateOrderCommand.php', '<?php // original');

        $this->generator->generate('Order', 'CreateOrder', $dir, 'App', ClassType::COMMAND->value);

        expect(file_get_contents($dir . '/CreateOrderCommand.php'))->toBe('<?php // original');
    });

    it('creates target directory recursively if missing', function (): void {
        $dir = $this->tmpDir . '/deep/nested/dir';
        $this->generator->generate('X', 'Foo', $dir, 'App', ClassType::ROLE->value, ['slug' => 'foo']);

        expect(is_dir($dir))->toBeTrue();
    });

    it('throws on unknown template type', function (): void {
        expect(fn() => $this->generator->generate('', 'Foo', $this->tmpDir, 'App', 'nonexistent.tpl'))
            ->toThrow(\RuntimeException::class);
    });

});

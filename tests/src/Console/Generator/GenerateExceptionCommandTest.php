<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateExceptionCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeExcContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed { return null; }
        public function has(string $id): bool  { return false; }
    };
}

function makeExceptionTester(): CommandTester
{
    $command = new GenerateExceptionCommand(makeExcContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:exception'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-exc-' . uniqid();
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

describe('GenerateExceptionCommand', function (): void {

    it('maps entity-not-found to EntityNotFoundException', function (): void {
        $tester = makeExceptionTester();
        $tester->execute(['ExceptionName' => 'OrderNotFound', 'type' => 'entity-not-found']);

        $content = (string) file_get_contents($this->cwd . '/src/Exception/OrderNotFoundException.php');
        expect($content)->toContain('extends EntityNotFoundException');
    });

    it('maps access-denied to AccessDeniedException', function (): void {
        $tester = makeExceptionTester();
        $tester->execute(['ExceptionName' => 'AdminOnly', 'type' => 'access-denied']);

        $content = (string) file_get_contents($this->cwd . '/src/Exception/AdminOnlyException.php');
        expect($content)->toContain('extends AccessDeniedException');
    });

    it('maps conflict to ConflictException', function (): void {
        $tester = makeExceptionTester();
        $tester->execute(['ExceptionName' => 'DuplicateEmail', 'type' => 'conflict']);

        $content = (string) file_get_contents($this->cwd . '/src/Exception/DuplicateEmailException.php');
        expect($content)->toContain('extends ConflictException');
    });

    it('maps validation to ValidationException', function (): void {
        $tester = makeExceptionTester();
        $tester->execute(['ExceptionName' => 'InvalidEmail', 'type' => 'validation']);

        $content = (string) file_get_contents($this->cwd . '/src/Exception/InvalidEmailException.php');
        expect($content)->toContain('extends ValidationException');
    });

    it('maps business-rule to BusinessRuleException', function (): void {
        $tester = makeExceptionTester();
        $tester->execute(['ExceptionName' => 'InsufficientStock', 'type' => 'business-rule']);

        $content = (string) file_get_contents($this->cwd . '/src/Exception/InsufficientStockException.php');
        expect($content)->toContain('extends BusinessRuleException');
    });

    it('prompts with choice list on invalid type', function (): void {
        $tester = makeExceptionTester();
        // 'bad-type' is invalid → ChoiceQuestion shown → select index 0 (entity-not-found)
        $tester->setInputs(['0']);
        $tester->execute(['ExceptionName' => 'Oops', 'type' => 'bad-type']);

        expect($tester->getStatusCode())->toBe(0);
        $content = (string) file_get_contents($this->cwd . '/src/Exception/OopsException.php');
        expect($content)->toContain('extends EntityNotFoundException');
    });

    it('accepts all args via interactive mode', function (): void {
        $tester = makeExceptionTester();
        // No args given → prompts for name then type (choice by index)
        $tester->setInputs(['StockInsufficient', '4']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/Exception/StockInsufficientException.php'))->toBeTrue();
    });

});

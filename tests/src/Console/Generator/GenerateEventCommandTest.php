<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateEventCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeEventContainer(): ContainerInterface
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

function makeEventTester(): CommandTester
{
    $command = new GenerateEventCommand(makeEventContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:event'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-event-' . uniqid();
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

describe('GenerateEventCommand', function (): void {

    it('generates a DomainEvent and EventHandler pair', function (): void {
        $tester = makeEventTester();
        $tester->execute(['EventName' => 'OrderPlaced']);

        $dir = $this->cwd . '/src/Event';
        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($dir . '/OrderPlacedDomainEvent.php'))->toBeTrue();
        expect(file_exists($dir . '/OrderPlacedEventHandler.php'))->toBeTrue();
    });

    it('EventHandler exposes __invoke(DomainEvent): void without annotations', function (): void {
        $tester = makeEventTester();
        $tester->execute(['EventName' => 'OrderPlaced']);

        $handler = (string) file_get_contents($this->cwd . '/src/Event/OrderPlacedEventHandler.php');
        expect($handler)
            ->toContain('public function __invoke(OrderPlacedDomainEvent $event): void')
            ->toContain('// TODO: handle event')
            ->not->toContain('#[')
            ->not->toContain('@');
    });

    it('accepts name via interactive mode', function (): void {
        $tester = makeEventTester();
        $tester->setInputs(['PaymentReceived']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/Event/PaymentReceivedDomainEvent.php'))->toBeTrue();
    });

});

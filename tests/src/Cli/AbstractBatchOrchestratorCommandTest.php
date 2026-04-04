<?php

declare(strict_types=1);

use Directive\Application\Event\DomainEventInterface;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Cli\AbstractBatchOrchestratorCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ---------------------------------------------------------------------------
// Test doubles
// ---------------------------------------------------------------------------

final class SpyBus implements DomainEventBusInterface
{
    /** @var DomainEventInterface[] */
    public array $dispatched = [];

    public function dispatch(DomainEventInterface $event): void
    {
        $this->dispatched[] = $event;
    }
}

final class StubEvent implements DomainEventInterface
{
    public function __construct(public readonly mixed $payload) {}

    public function getId(): string
    {
        return 'test-' . spl_object_id($this);
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}

function makeBatchContainer(DomainEventBusInterface $bus): ContainerInterface
{
    return new class ($bus) implements ContainerInterface {
        public function __construct(private readonly DomainEventBusInterface $bus) {}

        public function get(string $id): mixed
        {
            if ($id === DomainEventBusInterface::class) {
                return $this->bus;
            }
            return null;
        }

        public function has(string $id): bool
        {
            return $id === DomainEventBusInterface::class;
        }
    };
}

/** Make a batch command from an iterable source with a given chunk size. */
function makeBatch(iterable $source, int $chunkSize, SpyBus $bus): AbstractBatchOrchestratorCommand
{
    return new class ($source, $chunkSize, $bus) extends AbstractBatchOrchestratorCommand {
        public function __construct(
            private readonly iterable $source,
            private readonly int $size,
            SpyBus $bus,
        ) {
            parent::__construct(makeBatchContainer($bus));
        }

        protected function configure(): void
        {
            $this->setName('test:batch');
        }

        protected function getDataSource(): iterable
        {
            return $this->source;
        }

        protected function chunkMessage(mixed $item): DomainEventInterface
        {
            return new StubEvent($item);
        }

        protected function getChunkSize(): int
        {
            return $this->size;
        }
    };
}

// ---------------------------------------------------------------------------
// Dispatch count
// ---------------------------------------------------------------------------

describe('AbstractBatchOrchestratorCommand — dispatch count', function () {
    it('dispatches each item individually regardless of chunk size', function () {
        $bus = new SpyBus();
        $cmd = makeBatch([1, 2, 3, 4, 5], 2, $bus);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($bus->dispatched)->toHaveCount(5);
        expect($tester->getStatusCode())->toBe(0);
    });

    it('handles an empty source without dispatching anything', function () {
        $bus = new SpyBus();
        $cmd = makeBatch([], 3, $bus);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($bus->dispatched)->toHaveCount(0);
        expect($tester->getStatusCode())->toBe(0);
    });

    it('dispatches all items from a generator source', function () {
        $generator = (function () {
            yield from range(1, 10);
        })();

        $bus = new SpyBus();
        $cmd = makeBatch($generator, 3, $bus);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($bus->dispatched)->toHaveCount(10);
    });

    it('completes successfully when no logger is bound in the container', function () {
        // Container only has the bus — no LoggerInterface — dispatch must still work.
        $bus = new SpyBus();
        $cmd = makeBatch([1, 2, 3], 2, $bus);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($bus->dispatched)->toHaveCount(3);
        expect($tester->getStatusCode())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Invalid chunk size
// ---------------------------------------------------------------------------

describe('AbstractBatchOrchestratorCommand — chunk size validation', function () {
    it('throws LogicException when getChunkSize() returns 0', function () {
        $bus = new SpyBus();
        $cmd = makeBatch([1, 2, 3], 0, $bus);

        $tester = new CommandTester($cmd);

        expect(fn() => $tester->execute([]))
            ->toThrow(\LogicException::class);
    });

    it('throws LogicException when getChunkSize() returns a negative value', function () {
        $bus = new SpyBus();
        $cmd = makeBatch([1], -1, $bus);

        $tester = new CommandTester($cmd);

        expect(fn() => $tester->execute([]))
            ->toThrow(\LogicException::class);
    });
});

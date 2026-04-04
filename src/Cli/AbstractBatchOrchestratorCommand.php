<?php

declare(strict_types=1);

namespace Directive\Cli;

use Directive\Application\Event\DomainEventInterface;
use Directive\Application\EventBus\DomainEventBusInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base for batch orchestrator commands.
 *
 * Iterates a potentially large data source (generator-compatible) in chunks,
 * dispatching each item as a DomainEvent via DomainEventBusInterface.
 *
 * Subclasses implement three abstract methods:
 *   - getDataSource()  — lazy iterable (array, generator, …)
 *   - chunkMessage()   — converts a single item into a DomainEvent to dispatch
 *   - getChunkSize()   — maximum number of items per chunk (>= 1)
 *
 * Between chunks the command logs progression. The transport backing
 * DomainEventBusInterface is injected by the application (no Messenger dependency).
 */
abstract class AbstractBatchOrchestratorCommand extends DirectiveCommand
{
    // ------------------------------------------------------------------
    // Symfony Command lifecycle — sealed
    // ------------------------------------------------------------------

    final protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        $chunkSize = $this->getChunkSize();
        if ($chunkSize < 1) {
            throw new \LogicException(
                sprintf('getChunkSize() must return an integer >= 1, got %d.', $chunkSize),
            );
        }

        /** @var DomainEventBusInterface $bus */
        $bus = $this->container->get(DomainEventBusInterface::class);

        $logger = null;
        if ($this->container->has(LoggerInterface::class)) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
        }

        $name        = $this->getName() ?? 'unknown';
        $chunkNumber = 0;
        $chunkBuffer = [];
        $totalItems  = 0;

        foreach ($this->getDataSource() as $item) {
            $chunkBuffer[] = $item;
            ++$totalItems;

            if (count($chunkBuffer) >= $chunkSize) {
                $this->dispatchChunk($bus, $chunkBuffer);
                ++$chunkNumber;

                if ($logger !== null) {
                    $logger->info('batch.command.chunk', [
                        'command'        => $name,
                        'chunk'          => $chunkNumber,
                        'items_in_chunk' => count($chunkBuffer),
                    ]);
                }

                $chunkBuffer = [];
            }
        }

        // Flush the last (possibly incomplete) chunk.
        if ($chunkBuffer !== []) {
            $this->dispatchChunk($bus, $chunkBuffer);
            ++$chunkNumber;

            if ($logger !== null) {
                $logger->info('batch.command.chunk', [
                    'command'        => $name,
                    'chunk'          => $chunkNumber,
                    'items_in_chunk' => count($chunkBuffer),
                ]);
            }
        }

        $output->writeln(sprintf('[directive] Batch done: %d items in %d chunks.', $totalItems, $chunkNumber));

        return Command::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Abstract hooks
    // ------------------------------------------------------------------

    /**
     * Return the data source to iterate.
     *
     * Use a generator for memory-efficient processing of large datasets:
     *   yield from $repository->findAll();
     *
     * @return iterable<mixed>
     */
    abstract protected function getDataSource(): iterable;

    /**
     * Convert one data-source item into the DomainEvent to dispatch.
     */
    abstract protected function chunkMessage(mixed $item): DomainEventInterface;

    /**
     * Maximum number of items per chunk. Must be >= 1.
     */
    abstract protected function getChunkSize(): int;

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @param array<mixed> $items
     */
    private function dispatchChunk(DomainEventBusInterface $bus, array $items): void
    {
        foreach ($items as $item) {
            $bus->dispatch($this->chunkMessage($item));
        }
    }
}

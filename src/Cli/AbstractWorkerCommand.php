<?php

declare(strict_types=1);

namespace Directive\Cli;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstract base for long-running worker commands.
 *
 * Provides a controlled event-loop with graceful shutdown on SIGTERM/SIGINT:
 *   1. onStart() is called once before the loop.
 *   2. The loop calls run() repeatedly while $this->running is true
 *      (and while iteration count < maxIterations when maxIterations > 0).
 *   3. Exceptions from run() are passed to onError() — the loop continues.
 *   4. onStop() is called once after the loop exits.
 *
 * Process signals (requires the pcntl extension):
 *   SIGTERM / SIGINT set $this->running = false, allowing the current
 *   run() iteration to finish before the loop exits cleanly.
 */
abstract class AbstractWorkerCommand extends DirectiveCommand
{
    /** Set to false to stop the loop after the current run() iteration. */
    protected bool $running = true;

    /** 0 = run indefinitely; any positive int caps the iteration count. */
    protected int $maxIterations = 0;

    // ------------------------------------------------------------------
    // Symfony Command lifecycle — sealed
    // ------------------------------------------------------------------

    final protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        // Enable asynchronous signal dispatching so signal handlers trigger
        // between PHP opcode executions (requires the pcntl extension).
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
        }

        $stop = \Closure::fromCallable(function (): void {
            $this->running = false;
        });

        if (function_exists('pcntl_signal')) {
            pcntl_signal(\SIGTERM, $stop);
            pcntl_signal(\SIGINT, $stop);
        }

        $logger = null;
        if ($this->container->has(LoggerInterface::class)) {
            /** @var LoggerInterface $logger */
            $logger = $this->container->get(LoggerInterface::class);
        }

        $name       = $this->getName() ?? 'unknown';
        $iterations = 0;

        $this->onStart();

        while ($this->running) {
            if ($this->maxIterations > 0 && $iterations >= $this->maxIterations) {
                break;
            }

            try {
                $this->tick();
            } catch (\Throwable $e) {
                if ($logger !== null) {
                    $logger->error('worker.command.error', [
                        'command'   => $name,
                        'iteration' => $iterations,
                        'exception' => $e->getMessage(),
                    ]);
                }
                $this->onError($e);
            }

            ++$iterations;
        }

        $this->onStop();

        return Command::SUCCESS;
    }

    // ------------------------------------------------------------------
    // Lifecycle hooks — override as needed
    // ------------------------------------------------------------------

    /** Called once before the event loop starts. */
    protected function onStart(): void {}

    /** Called once after the event loop exits. */
    protected function onStop(): void {}

    /** Called for each exception thrown by run(); the loop continues. */
    protected function onError(\Throwable $e): void {}

    // ------------------------------------------------------------------
    // Abstract hook
    // ------------------------------------------------------------------

    /**
     * One iteration of the worker loop.
     *
     * Implement your message consumption, job execution, or polling logic here.
     * This method is called repeatedly until the loop stops.
     */
    abstract protected function tick(): void;
}

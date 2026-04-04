<?php

declare(strict_types=1);

use Directive\Cli\AbstractWorkerCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeWorkerContainer(): ContainerInterface
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

/** Build a concrete AbstractWorkerCommand with a given tick() body. */
function makeWorker(callable $tickFn, int $maxIterations = 1): AbstractWorkerCommand
{
    return new class ($tickFn, $maxIterations, makeWorkerContainer()) extends AbstractWorkerCommand {
        public function __construct(
            private readonly \Closure $tickFn,
            int $max,
            ContainerInterface $container,
        ) {
            $this->maxIterations = $max;
            parent::__construct($container);
        }

        protected function configure(): void
        {
            $this->setName('test:worker');
        }

        protected function tick(): void
        {
            ($this->tickFn)();
        }
    };
}

// ---------------------------------------------------------------------------
// Iteration control
// ---------------------------------------------------------------------------

describe('AbstractWorkerCommand — iteration control', function () {
    it('calls tick() exactly maxIterations times', function () {
        $count = 0;
        $cmd   = makeWorker(function () use (&$count): void {
            ++$count;
        }, maxIterations: 3);

        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($count)->toBe(3);
        expect($tester->getStatusCode())->toBe(0); // Command::SUCCESS
    });

    it('returns SUCCESS after normal completion', function () {
        $cmd    = makeWorker(function (): void {}, maxIterations: 1);
        $tester = new CommandTester($cmd);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Exception handling — onError keeps the loop running
// ---------------------------------------------------------------------------

describe('AbstractWorkerCommand — error resilience', function () {
    it('calls onError() for each exception and continues the loop', function () {
        $errors = [];

        $worker = new class (makeWorkerContainer()) extends AbstractWorkerCommand {
            public int $tickCount = 0;

            /** @var \Throwable[] */
            public array $capturedErrors = [];

            protected function configure(): void
            {
                $this->setName('test:resilient');
            }

            protected function tick(): void
            {
                ++$this->tickCount;
                if ($this->tickCount <= 2) {
                    throw new \RuntimeException('tick error ' . $this->tickCount);
                }
            }

            protected function onError(\Throwable $e): void
            {
                $this->capturedErrors[] = $e;
            }

            public function __construct(ContainerInterface $c)
            {
                $this->maxIterations = 3;
                parent::__construct($c);
            }
        };

        $tester = new CommandTester($worker);
        $tester->execute([]);

        expect($worker->tickCount)->toBe(3);
        expect($worker->capturedErrors)->toHaveCount(2);
        expect($tester->getStatusCode())->toBe(0);
    });
});

// ---------------------------------------------------------------------------
// Lifecycle hooks — onStart / onStop
// ---------------------------------------------------------------------------

describe('AbstractWorkerCommand — lifecycle hooks', function () {
    it('calls onStart() once before the loop and onStop() once after', function () {
        $worker = new class (makeWorkerContainer()) extends AbstractWorkerCommand {
            public int $startCount = 0;
            public int $stopCount  = 0;

            protected function configure(): void
            {
                $this->setName('test:lifecycle');
            }

            public function __construct(ContainerInterface $c)
            {
                $this->maxIterations = 2;
                parent::__construct($c);
            }

            protected function tick(): void {}

            protected function onStart(): void
            {
                ++$this->startCount;
            }

            protected function onStop(): void
            {
                ++$this->stopCount;
            }
        };

        $tester = new CommandTester($worker);
        $tester->execute([]);

        expect($worker->startCount)->toBe(1);
        expect($worker->stopCount)->toBe(1);
    });
});

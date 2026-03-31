<?php

declare(strict_types=1);

use Directive\Application\Command\AbstractCommand;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\EventBus\NullDomainEventBus;
use Directive\Application\Exception\AccessDeniedException;
use Directive\Application\Exception\EntityNotFoundException;
use Directive\Application\Message\AbstractResult;
use Directive\Application\Message\PayloadInterface;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Query\AbstractQuery;
use Directive\Application\UseCase\AbstractUseCase;
use Psr\Log\NullLogger;

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

class UseCaseTestCommand extends AbstractCommand {}

class UseCasePayload implements PayloadInterface {}

class UseCaseTestResult extends AbstractResult
{
    public function __construct()
    {
        $this->setPayload(new UseCasePayload());
    }
}

function makeUseCase(callable $executeImpl): AbstractUseCase
{
    return new class($executeImpl, new NullLogger(), new NullDomainEventBus()) extends AbstractUseCase {
        public function __construct(
            private readonly mixed $impl,
            \Psr\Log\LoggerInterface $logger,
            DomainEventBusInterface $eventBus,
        ) {
            parent::__construct($logger, $eventBus);
        }

        protected function execute(AbstractCommand|\Directive\Application\Query\AbstractQuery $input): ResultInterface
        {
            return ($this->impl)($input);
        }
    };
}

// ---------------------------------------------------------------------------
// AbstractUseCase
// ---------------------------------------------------------------------------

describe('AbstractUseCase', function () {
    it('handle() returns the result of execute()', function () {
        $useCase = makeUseCase(fn ($input) => new UseCaseTestResult());
        $result = $useCase->handle(new UseCaseTestCommand());
        expect($result)->toBeInstanceOf(ResultInterface::class);
        expect($result->getData())->toBeInstanceOf(UseCasePayload::class);
    });

    it('onSuccess() callback is called after successful execute()', function () {
        $called = false;
        $useCase = new class(new NullLogger(), new NullDomainEventBus()) extends AbstractUseCase {
            public bool $callbackCalled = false;

            protected function execute(AbstractCommand|AbstractQuery $input): ResultInterface
            {
                $this->onSuccess(function () {
                    $this->callbackCalled = true;
                });

                return new UseCaseTestResult();
            }
        };

        $useCase->handle(new UseCaseTestCommand());
        expect($useCase->callbackCalled)->toBeTrue();
    });

    it('onError() callback is called when execute() throws', function () {
        $errorCaught = null;
        $useCase = new class(new NullLogger(), new NullDomainEventBus()) extends AbstractUseCase {
            public mixed $caughtError = null;

            protected function execute(AbstractCommand|AbstractQuery $input): ResultInterface
            {
                $this->onError(function (\Throwable $e) {
                    $this->caughtError = $e;
                });

                throw new EntityNotFoundException('not found');
            }
        };

        try {
            $useCase->handle(new UseCaseTestCommand());
        } catch (EntityNotFoundException) {
            // expected
        }

        expect($useCase->caughtError)->toBeInstanceOf(EntityNotFoundException::class);
    });

    it('handle() propagates AccessDeniedException', function () {
        $useCase = makeUseCase(fn () => throw new AccessDeniedException('denied'));
        expect(fn () => $useCase->handle(new UseCaseTestCommand()))
            ->toThrow(AccessDeniedException::class);
    });

    it('handle() propagates EntityNotFoundException', function () {
        $useCase = makeUseCase(fn () => throw new EntityNotFoundException('not found'));
        expect(fn () => $useCase->handle(new UseCaseTestCommand()))
            ->toThrow(EntityNotFoundException::class);
    });

    it('onSuccess() callback is NOT called when execute() throws', function () {
        $called = false;
        $useCase = new class(new NullLogger(), new NullDomainEventBus()) extends AbstractUseCase {
            public bool $successCalled = false;

            protected function execute(AbstractCommand|AbstractQuery $input): ResultInterface
            {
                $this->onSuccess(function () {
                    $this->successCalled = true;
                });

                throw new EntityNotFoundException('oops');
            }
        };

        try {
            $useCase->handle(new UseCaseTestCommand());
        } catch (EntityNotFoundException) {}

        expect($useCase->successCalled)->toBeFalse();
    });
});

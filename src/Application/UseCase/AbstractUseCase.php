<?php

declare(strict_types=1);

namespace Directive\Application\UseCase;

use Directive\Application\Command\AbstractCommand;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\Exception\AbstractDomainException;
use Directive\Application\Exception\AccessDeniedException;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Query\AbstractQuery;
use Psr\Log\LoggerInterface;

abstract class AbstractUseCase implements UseCaseInterface
{
    /** @var list<callable> */
    private array $successCallbacks = [];

    /** @var list<callable> */
    private array $errorCallbacks = [];

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly DomainEventBusInterface $eventBus,
    ) {
    }

    abstract protected function execute(AbstractCommand|AbstractQuery $input): ResultInterface;

    final public function handle(AbstractCommand|AbstractQuery $input): ResultInterface
    {
        $this->successCallbacks = [];
        $this->errorCallbacks = [];

        try {
            $result = $this->execute($input);

            /** @phpstan-ignore foreach.emptyArray */
            foreach ($this->successCallbacks as $callback) {
                ($callback)($result);
            }

            return $result;
        } catch (AccessDeniedException $e) {
            $this->logger->warning(
                sprintf('[%s] Access denied: %s', static::class, $e->getMessage()),
                $e->getContext(),
            );

            /** @phpstan-ignore foreach.emptyArray */
            foreach ($this->errorCallbacks as $callback) {
                ($callback)($e);
            }

            throw $e;
        } catch (AbstractDomainException $e) {
            $this->logger->error(
                sprintf('[%s] Domain exception: %s', static::class, $e->getMessage()),
                $e->getContext(),
            );

            /** @phpstan-ignore foreach.emptyArray */
            foreach ($this->errorCallbacks as $callback) {
                ($callback)($e);
            }

            throw $e;
        }
    }

    protected function onSuccess(callable $callback): void
    {
        $this->successCallbacks[] = $callback;
    }

    protected function onError(callable $callback): void
    {
        $this->errorCallbacks[] = $callback;
    }
}

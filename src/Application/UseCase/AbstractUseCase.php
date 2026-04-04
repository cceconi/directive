<?php

declare(strict_types=1);

namespace Directive\Application\UseCase;

use Directive\Application\Command\AbstractCommand;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\Exception\AbstractDomainException;
use Directive\Application\Exception\AccessDeniedException;
use Directive\Application\Message\ResultInterface;
use Directive\Application\Query\AbstractQuery;
use Directive\Application\Role\AbstractRole;
use Directive\Application\Role\Permission;
use Directive\Application\User\DomainUser;
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
    ) {}

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

    // ------------------------------------------------------------------
    // UCAC — fine-grained permission control
    // ------------------------------------------------------------------

    /**
     * Declare the permission matrix for this use-case.
     * Keys are role class-strings, values are Permission enum cases.
     * Roles not listed default to Permission::Allow.
     *
     * @return array<class-string<AbstractRole>, Permission>
     */
    protected function getPermissions(): array
    {
        return [];
    }

    /**
     * Check if the caller is allowed to execute this use-case.
     * Call explicitly at the top of execute() before any business logic.
     *
     * - Permission::Allow       → no-op
     * - Permission::Forbidden   → throws AccessDeniedException
     * - Permission::Complementary → evaluates the matching closure in $complementaryRules;
     *                               throws AccessDeniedException if absent or returns false
     *
     * @param array<class-string<AbstractRole>, callable(): bool> $complementaryRules
     */
    protected function hasPermission(DomainUser $caller, array $complementaryRules = []): void
    {
        $permissions = $this->getPermissions();
        $permission  = $permissions[$caller->role::class] ?? Permission::Allow;

        match ($permission) {
            Permission::Forbidden     => throw new AccessDeniedException(),
            Permission::Allow         => null,
            Permission::Complementary => $this->resolveComplementary($caller->role::class, $complementaryRules),
        };
    }

    /**
     * @param array<class-string<AbstractRole>, callable(): bool> $rules
     */
    private function resolveComplementary(string $roleClass, array $rules): void
    {
        $rule = $rules[$roleClass] ?? null;
        if ($rule === null || !$rule()) {
            throw new AccessDeniedException();
        }
    }
}

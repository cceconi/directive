<?php

declare(strict_types=1);

namespace Tests\Bridge;

use Directive\Application\Command\AbstractCommand;
use Directive\Application\Message\AbstractResult;
use Directive\Application\Message\PayloadInterface;
use Directive\Application\UseCase\UseCaseInterface;
use Directive\Application\User\DomainUser;

// ---------------------------------------------------------------------------
// CreateUserCommand
// ---------------------------------------------------------------------------

final class CreateUserCommand extends AbstractCommand
{
    public function __construct(
        public readonly string $email,
        public readonly DomainUser $caller,
    ) {
        parent::__construct();
    }
}

// ---------------------------------------------------------------------------
// CreateUserPayload
// ---------------------------------------------------------------------------

final class CreateUserPayload implements PayloadInterface
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
    ) {}
}

// ---------------------------------------------------------------------------
// CreateUserResult
// ---------------------------------------------------------------------------

final class CreateUserResult extends AbstractResult
{
    public function __construct(CreateUserPayload $payload)
    {
        $this->setPayload($payload);
    }
}

// ---------------------------------------------------------------------------
// CreateUserUseCaseInterface
// ---------------------------------------------------------------------------

interface CreateUserUseCaseInterface extends UseCaseInterface {}

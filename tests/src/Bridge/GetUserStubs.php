<?php

declare(strict_types=1);

namespace Tests\Bridge;

use Directive\Application\Message\AbstractResult;
use Directive\Application\Message\PayloadInterface;
use Directive\Application\Query\AbstractQuery;
use Directive\Application\UseCase\UseCaseInterface;
use Directive\Application\User\DomainUser;

// ---------------------------------------------------------------------------
// GetUserQuery
// ---------------------------------------------------------------------------

final class GetUserQuery extends AbstractQuery
{
    public function __construct(
        public readonly string $id,
        public readonly DomainUser $caller,
    ) {
        parent::__construct();
    }
}

// ---------------------------------------------------------------------------
// GetUserPayload
// ---------------------------------------------------------------------------

final class GetUserPayload implements PayloadInterface
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {}
}

// ---------------------------------------------------------------------------
// GetUserResult
// ---------------------------------------------------------------------------

final class GetUserResult extends AbstractResult
{
    public function __construct(GetUserPayload $payload)
    {
        $this->setPayload($payload);
    }
}

// ---------------------------------------------------------------------------
// GetUserUseCaseInterface
// ---------------------------------------------------------------------------

interface GetUserUseCaseInterface extends UseCaseInterface {}

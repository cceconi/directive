<?php

declare(strict_types=1);

namespace Directive\Service\Security\Role;

/**
 * Immutable value object carrying the authenticated user's identity and role
 * into the application's Use-Cases.
 *
 * Built from WebUserInterface and passed as a parameter to UseCase::execute().
 */
readonly class DomainUser
{
    public function __construct(
        public string       $id,
        public AbstractRole $role,
    ) {}
}
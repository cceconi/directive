<?php

declare(strict_types=1);

namespace Directive\Application\Role;

/**
 * Represents an anonymous (unauthenticated) user.
 */
class GuestRole extends AbstractRole
{
    public function slug(): string
    {
        return 'guest';
    }
}

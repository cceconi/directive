<?php

declare(strict_types=1);

namespace Directive\Service\Security\Access\Type;

/**
 * Anonymous access — no credentials presented.
 * Always succeeds; the WebUser stays as GUEST.
 */
final class NoneAccess extends AbstractAccess
{
    public function authenticate(): bool
    {
        return true;
    }
}

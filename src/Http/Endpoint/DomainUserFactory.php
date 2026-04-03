<?php

declare(strict_types=1);

namespace Directive\Http\Endpoint;

use Directive\Application\User\DomainUser;
use Directive\Service\Security\WebUserInterface;

/**
 * Converts a WebUserInterface (HTTP layer) to a DomainUser (Application layer).
 *
 * Pure static utility — no dependencies, no DI.
 */
final class DomainUserFactory
{
    public static function fromWebUser(WebUserInterface $webUser): DomainUser
    {
        return new DomainUser(
            id: $webUser->getId(),
            role: $webUser->getRole(),
        );
    }
}

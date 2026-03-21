<?php

declare(strict_types=1);

namespace Directive\Service\Security\Role;

/**
 * UCAC permission values.
 *
 * - Allow       : access granted unconditionally by the role
 * - Complementary: access granted at the role level; the UseCase performs additional checks
 *                  (e.g. a customer may read *their own* orders only)
 * - Forbidden   : access denied
 */
enum Permission: string
{
    case Allow         = 'allow';
    case Complementary = 'complementary';
    case Forbidden     = 'forbidden';
}

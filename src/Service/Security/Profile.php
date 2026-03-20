<?php

declare(strict_types=1);

namespace Directive\Service\Security;

/**
 * Base profile constants.
 * User applications extend this interface to add their own profiles.
 *
 * Usage:
 *   class MyProfile extends Profile {
 *       const string CUSTOMER = 'customer';
 *       const string ADMIN    = 'admin';
 *   }
 *
 * @deprecated Use {@see \Directive\Service\Security\Role\AbstractRole} and its subclasses instead.
 */
interface Profile
{
    const string GUEST = 'guest';
}

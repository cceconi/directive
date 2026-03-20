<?php

declare(strict_types=1);

namespace Directive\Rest;

/**
 * Static entry point for the fluent API tree builder.
 *
 * Usage:
 *   $domain = ApiTree::domain('users');
 *   $v1     = $domain->version('v1', VersionStatus::Open);
 *   $acct   = $v1->service('account');
 *
 *   $acct->resource('profile')
 *       ->get(GetProfile::class, policyClass: GetProfilePolicy::class, allowedRoles: ['user', 'admin'])
 *       ->put(PutProfile::class, policyClass: PutProfilePolicy::class, allowedRoles: ['user']);
 *
 *   $acct->resource('settings')
 *       ->get(GetSettings::class, policyClass: GetSettingsPolicy::class);
 */
final class ApiTree
{
    /** Prevent instantiation — this is a static factory only. */
    private function __construct() {}

    public static function domain(string $name): Domain
    {
        return new Domain($name);
    }
}

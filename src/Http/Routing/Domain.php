<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\NotFoundException;

/**
 * Top-level node of the API tree: a named collection of Versions.
 *
 * Fluent usage — start here:
 *   $domain = new Domain('users');
 *   $domain->version('v1', VersionStatus::Open)
 *       ->service('account')
 *           ->resource('profile')->get(...)
 *
 * Cascading defaults — set once, propagate down:
 *   $domain->withErrorClass(AppErrorManager::class)
 *       ->version('v1')->service('account')->resource('profile')->get(...)
 */
class Domain
{
    /** @var array<string, Version> */
    private array $versions = [];

    public function __construct(
        public private(set) readonly string $name,
        private MethodDefaults $defaults = new MethodDefaults(),
    ) {}

    /** @param class-string $class */
    public function withErrorClass(string $class): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withErrorClass($class);
        return $clone;
    }

    /** @param class-string $class */
    public function withRequestValidatorClass(string $class): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withRequestValidatorClass($class);
        return $clone;
    }

    /** @param array<string> $roles */
    public function withAllowedRoles(array $roles): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withAllowedRoles($roles);
        return $clone;
    }

    /** @param array<int> $codes */
    public function withErrorCodes(array $codes): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withErrorCodes($codes);
        return $clone;
    }

    public function withAuthenticated(bool $authenticated): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withAuthenticated($authenticated);
        return $clone;
    }

    /**
     * Create a new Version and register it in this domain.
     *
     * @throws ApiDefinitionException on duplicate version name
     */
    public function version(
        string $name,
        VersionStatus $status = VersionStatus::Open,
        string $info = '',
    ): Version {
        if (isset($this->versions[$name])) {
            throw new ApiDefinitionException(
                sprintf('Version "%s" is already registered in domain "%s".', $name, $this->name),
            );
        }

        $version = new Version($name, $status, $info, $this->defaults);
        $this->versions[$name] = $version;

        return $version;
    }

    /**
     * @throws NotFoundException
     */
    public function findVersion(string $name): Version
    {
        return $this->versions[$name] ?? throw new NotFoundException(
            sprintf('Version "%s" not found in domain "%s".', $name, $this->name),
        );
    }

    /**
     * Return all registered versions (used by the OpenAPI exporter).
     *
     * @return array<string, Version>
     */
    public function getVersions(): array
    {
        return $this->versions;
    }
}

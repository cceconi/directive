<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\GoneException;
use Directive\Exception\NotFoundException;

/**
 * A named, versioned collection of Services.
 * Carries a VersionStatus that controls its availability at runtime.
 *
 * Fluent usage:
 *   $domain->version('v1', VersionStatus::Open)
 *       ->service('account')->resource('profile')->get(...)
 */
class Version
{
    /** @var array<string, Service> */
    private array $services = [];

    public function __construct(
        public private(set) readonly string $name,
        public private(set) readonly VersionStatus $status = VersionStatus::Open,
        public private(set) readonly string $info = '',
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

    /**
     * Create a new Service and register it in this version.
     *
     * @throws ApiDefinitionException on duplicate service name
     */
    public function service(string $name): Service
    {
        if (isset($this->services[$name])) {
            throw new ApiDefinitionException(
                sprintf('Service "%s" is already registered in version "%s".', $name, $this->name),
            );
        }

        $service = new Service($name, $this->defaults);
        $this->services[$name] = $service;

        return $service;
    }

    /**
     * @throws NotFoundException
     */
    public function findService(string $name): Service
    {
        return $this->services[$name] ?? throw new NotFoundException(
            sprintf('Service "%s" not found in version "%s".', $name, $this->name),
        );
    }

    /**
     * Return all registered services (used by the OpenAPI exporter).
     *
     * @return array<string, Service>
     */
    public function getServices(): array
    {
        return $this->services;
    }

    /**
     * Assert this version is publicly available.
     *
     * @throws GoneException when status is Closed or Wip
     */
    public function checkAvailability(): void
    {
        if ($this->status === VersionStatus::Closed || $this->status === VersionStatus::Wip) {
            throw new GoneException(
                sprintf(
                    'API version "%s" is not available (status: %s).',
                    $this->name,
                    $this->status->value,
                ),
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\GoneException;
use Directive\Http\Exception\NotFoundException;

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

    /** @var array<string, Resource> */
    private array $directResources = [];

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

    public function withRateLimit(RateLimit $rateLimit): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withRateLimit($rateLimit);
        return $clone;
    }

    public function withRateLimitKeyType(RateLimitKeyType $rateLimitKeyType): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withRateLimitKeyType($rateLimitKeyType);
        return $clone;
    }

    public function withRateLimitEnabled(bool $rateLimitEnabled): static
    {
        $clone           = clone $this;
        $clone->defaults = $this->defaults->withRateLimitEnabled($rateLimitEnabled);
        return $clone;
    }

    /**
     * Create a new Service and register it in this version.
     *
     * @throws ApiDefinitionException on duplicate service name or collision with a direct resource
     */
    public function service(string $name): Service
    {
        if (isset($this->services[$name])) {
            throw new ApiDefinitionException(
                sprintf('Service "%s" is already registered in version "%s".', $name, $this->name),
            );
        }

        if (isset($this->directResources[$name])) {
            throw new ApiDefinitionException(
                sprintf('Name "%s" is already registered as a direct resource in version "%s".', $name, $this->name),
            );
        }

        $service = new Service($name, $this->defaults);
        $this->services[$name] = $service;

        return $service;
    }

    /**
     * Create a new Resource directly on this version (bypassing the Service layer).
     *
     * @throws ApiDefinitionException on duplicate name or collision with a service
     */
    public function resource(string $name): Resource
    {
        if (isset($this->directResources[$name])) {
            throw new ApiDefinitionException(
                sprintf('Direct resource "%s" is already registered in version "%s".', $name, $this->name),
            );
        }

        if (isset($this->services[$name])) {
            throw new ApiDefinitionException(
                sprintf('Name "%s" is already registered as a service in version "%s".', $name, $this->name),
            );
        }

        $resource = new Resource($name, $this->defaults);
        $this->directResources[$name] = $resource;

        return $resource;
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
     * @throws NotFoundException
     */
    public function findResource(string $name): Resource
    {
        return $this->directResources[$name] ?? throw new NotFoundException(
            sprintf('Direct resource "%s" not found in version "%s".', $name, $this->name),
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
     * Return all directly-registered resources (used by the OpenAPI exporter).
     *
     * @return array<string, Resource>
     */
    public function getDirectResources(): array
    {
        return $this->directResources;
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

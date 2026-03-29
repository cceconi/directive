<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\NotFoundException;

/**
 * A named collection of Resources within a Version.
 *
 * Fluent usage:
 *   $version->service('account')
 *       ->resource('profile')->get(...)
 */
class Service
{
    /** @var array<string, Resource> */
    private array $resources = [];

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
     * Create a new Resource and register it in this service.
     *
     * @throws ApiDefinitionException on duplicate resource name
     */
    public function resource(string $name): Resource
    {
        if (isset($this->resources[$name])) {
            throw new ApiDefinitionException(
                sprintf('Resource "%s" is already registered in service "%s".', $name, $this->name),
            );
        }

        $resource = new Resource($name, $this->defaults);
        $this->resources[$name] = $resource;

        return $resource;
    }

    /**
     * @throws NotFoundException
     */
    public function findResource(string $name): Resource
    {
        return $this->resources[$name] ?? throw new NotFoundException(
            sprintf('Resource "%s" not found in service "%s".', $name, $this->name),
        );
    }

    /**
     * Return all registered resources (used by the OpenAPI exporter).
     *
     * @return array<string, Resource>
     */
    public function getResources(): array
    {
        return $this->resources;
    }
}

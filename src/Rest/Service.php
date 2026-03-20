<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\NotFoundException;

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
    ) {}

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

        $resource = new Resource($name);
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

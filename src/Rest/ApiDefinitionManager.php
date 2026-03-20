<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\NotFoundException;

/**
 * Registry of Domain objects.
 *
 * Registered during application boot via Configuration::registerApi().
 * Used at runtime by the Router to find domains by name.
 */
class ApiDefinitionManager
{
    /** @var array<string, Domain> */
    private array $domains = [];

    /**
     * @throws ApiDefinitionException on duplicate domain name
     */
    public function registerDomain(Domain $domain): void
    {
        if (isset($this->domains[$domain->name])) {
            throw new ApiDefinitionException(
                sprintf('Domain "%s" is already registered.', $domain->name),
            );
        }

        $this->domains[$domain->name] = $domain;
    }

    /**
     * @throws NotFoundException
     */
    public function findDomain(string $name): Domain
    {
        return $this->domains[$name] ?? throw new NotFoundException(
            sprintf('Domain "%s" not found.', $name),
        );
    }

    /**
     * Return all registered domains (used by the OpenAPI exporter).
     *
     * @return array<string, Domain>
     */
    public function getDomains(): array
    {
        return $this->domains;
    }
}

<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\NotFoundException;

/**
 * Top-level node of the API tree: a named collection of Versions.
 *
 * Fluent usage — start here:
 *   ApiTree::domain('users')
 *       ->version('v1', VersionStatus::Open)
 *           ->service('account')
 *               ->resource('profile')->get(...)
 */
class Domain
{
    /** @var array<string, Version> */
    private array $versions = [];

    public function __construct(
        public private(set) readonly string $name,
    ) {}

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

        $version = new Version($name, $status, $info);
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

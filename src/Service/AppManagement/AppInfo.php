<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

/**
 * Read-only VO wrapping the app-info JSON file.
 *
 * Expected JSON shape:
 *   { name, version, internal, env, commitId, branch, at }
 */
final class AppInfo implements AppInfoInterface
{
    /** @param array<string, mixed> $data */
    public function __construct(private readonly array $data = [])
    {
    }

    public function getName(): string
    {
        return (string) ($this->data['name'] ?? '');
    }

    public function getVersion(): string
    {
        return (string) ($this->data['version'] ?? '');
    }

    /** @return array<string, mixed> */
    public function readVersion(): array
    {
        return array_intersect_key($this->data, array_flip(['name', 'version', 'env']));
    }

    /** @return array<string, mixed> */
    public function readAll(): array
    {
        return array_intersect_key(
            $this->data,
            array_flip(['name', 'version', 'internal', 'env', 'commitId', 'branch', 'at']),
        );
    }
}

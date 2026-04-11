<?php

declare(strict_types=1);

namespace Directive\Service\Maintenance;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultMaintenanceConfig implements ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('MAINTENANCE_SECRET_KEY', '', 'string');
        $config->optional('MAINTENANCE_FILENAME', 'var/maintenance.json', 'string');
    }

    public function getSecretKey(): string
    {
        return (string) $this->config->get('MAINTENANCE_SECRET_KEY');
    }

    public function getFilename(): string
    {
        return (string) $this->config->get('MAINTENANCE_FILENAME');
    }
}

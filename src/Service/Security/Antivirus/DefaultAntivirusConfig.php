<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

final class DefaultAntivirusConfig implements AntivirusConfigInterface, ConfigProviderInterface
{
    public function __construct(private Configuration $config) {}

    public function define(Configuration $config): void
    {
        $config->optional('ANTIVIRUS_NAME', 'clamav', 'string');
        $config->optional('ANTIVIRUS_HOST', '127.0.0.1', 'string');
        $config->optional('ANTIVIRUS_PORT', 3310, 'int');
        $config->optional('ANTIVIRUS_TIMEOUT', 5, 'int');
    }

    public function getName(): string
    {
        return (string) $this->config->get('ANTIVIRUS_NAME');
    }

    public function getHost(): string
    {
        return (string) $this->config->get('ANTIVIRUS_HOST');
    }

    public function getPort(): int
    {
        return (int) $this->config->get('ANTIVIRUS_PORT');
    }

    public function getTimeout(): int
    {
        return (int) $this->config->get('ANTIVIRUS_TIMEOUT');
    }
}

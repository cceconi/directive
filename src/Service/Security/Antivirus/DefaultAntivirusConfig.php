<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultAntivirusConfig implements AntivirusConfigInterface
{
    public function __construct(private AbstractConfiguration $config) {
        $this->define();
    }

    protected function define(): void
    {
        $this->config->optional('ANTIVIRUS_NAME', 'clamav', 'string');
        $this->config->optional('ANTIVIRUS_HOST', '127.0.0.1', 'string');
        $this->config->optional('ANTIVIRUS_PORT', 3310, 'int');
        $this->config->optional('ANTIVIRUS_TIMEOUT', 5, 'int');
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

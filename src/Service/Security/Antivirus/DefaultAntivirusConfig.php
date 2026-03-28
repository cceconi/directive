<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Directive\Service\Configuration\AbstractConfiguration;

final class DefaultAntivirusConfig extends AbstractConfiguration implements AntivirusConfigInterface
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_ANTIVIRUS_NAME', 'clamav', 'string');
        $this->optional('DIRECTIVE_ANTIVIRUS_HOST', '127.0.0.1', 'string');
        $this->optional('DIRECTIVE_ANTIVIRUS_PORT', 3310, 'int');
        $this->optional('DIRECTIVE_ANTIVIRUS_TIMEOUT', 5, 'int');
    }

    public function getName(): string
    {
        return (string) $this->get('DIRECTIVE_ANTIVIRUS_NAME');
    }

    public function getHost(): string
    {
        return (string) $this->get('DIRECTIVE_ANTIVIRUS_HOST');
    }

    public function getPort(): int
    {
        return (int) $this->get('DIRECTIVE_ANTIVIRUS_PORT');
    }

    public function getTimeout(): int
    {
        return (int) $this->get('DIRECTIVE_ANTIVIRUS_TIMEOUT');
    }
}

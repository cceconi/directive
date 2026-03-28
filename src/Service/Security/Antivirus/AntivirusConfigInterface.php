<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

interface AntivirusConfigInterface
{
    public function getHost(): string;
    public function getPort(): int;
    public function getTimeout(): int;
    public function getName(): string;
}

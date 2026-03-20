<?php

declare(strict_types=1);

namespace Directive\Service\Security\Antivirus;

use Psr\Log\LoggerInterface;

interface AntivirusServiceInterface
{
    public function create(): AntivirusInterface;

    public function getLogger(): LoggerInterface;
}

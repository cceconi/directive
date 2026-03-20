<?php

declare(strict_types=1);

namespace Directive\Service\AppManagement;

/**
 * Exposes metadata about the running application.
 * Concrete implementation lives in Epic 8.
 */
interface AppInfoInterface
{
    public function getVersion(): string;

    public function getName(): string;
}

<?php

declare(strict_types=1);

namespace Directive\Application\Role;

/**
 * Represents an internal machine/system call (cron, CLI, internal service).
 */
class SystemRole extends AbstractRole
{
    public function slug(): string
    {
        return 'system';
    }
}

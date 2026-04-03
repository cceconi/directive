<?php

declare(strict_types=1);

namespace Directive\Application\Role;

/**
 * Represents an AI agent or automated caller.
 */
class AgentRole extends AbstractRole
{
    public function slug(): string
    {
        return 'agent';
    }
}

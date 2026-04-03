<?php

declare(strict_types=1);

namespace {{namespace}}\Shared\Role;

use Directive\Application\Role\AbstractRole;

final class {{name}}Role extends AbstractRole
{
    public function slug(): string
    {
        return '{{slug}}';
    }
}

<?php

declare(strict_types=1);

namespace Directive\Application\Message;

use Directive\Application\Role\AbstractRole;

interface FilterInterface
{
    public function filter(mixed $data, AbstractRole $role): mixed;
}

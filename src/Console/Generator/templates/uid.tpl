<?php

declare(strict_types=1);

namespace {{namespace}}\Model;

use Directive\Application\Model\AbstractUid;
use Ramsey\Uuid\Uuid;

final class {{name}}Id extends AbstractUid
{
    public static function generate(): self
    {
        return new self(Uuid::uuid7()->toString());
    }
}

<?php

declare(strict_types=1);

namespace Directive\Application\Model;

abstract class AbstractEntity
{
    abstract public function getId(): AbstractUid;
}

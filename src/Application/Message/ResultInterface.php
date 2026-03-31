<?php

declare(strict_types=1);

namespace Directive\Application\Message;

interface ResultInterface
{
    public function getData(): mixed;
}

<?php

declare(strict_types=1);

namespace Directive\Application\Message;

interface PresenterInterface
{
    public function present(mixed $data): mixed;
}

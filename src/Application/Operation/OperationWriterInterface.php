<?php

declare(strict_types=1);

namespace Directive\Application\Operation;

interface OperationWriterInterface
{
    public function write(string $operation, mixed $context = null): void;
}

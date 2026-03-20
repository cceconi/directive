<?php

declare(strict_types=1);

namespace Directive\Service\Business;

interface ErrorInterface
{
    public function add(string $property, string $message, string $type = 'error'): void;

    /** @return array<array<string, string>> */
    public function getErrors(): array;

    /** @return array<array<string, string>> */
    public function getErrorsByType(string $type): array;

    public function hasErrors(): bool;

    public function reset(): void;
}

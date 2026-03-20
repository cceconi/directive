<?php

declare(strict_types=1);

namespace Directive\Service\Business;

final class ErrorManager implements ErrorInterface
{
    /** @var array<array<string, string>> */
    private array $errors = [];

    public function add(string $property, string $message, string $type = 'error'): void
    {
        $this->errors[] = [
            'type'     => $type,
            'property' => $property,
            'message'  => $message,
        ];
    }

    /** @return array<array<string, string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /** @return array<array<string, string>> */
    public function getErrorsByType(string $type): array
    {
        return array_values(
            array_filter($this->errors, fn (array $e) => $e['type'] === $type),
        );
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function reset(): void
    {
        $this->errors = [];
    }
}

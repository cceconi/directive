<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Maps to HTTP 409. Business rule violation with structured error payload.
 *
 * Use the fluent withErrors() factory to attach a structured error list:
 *   throw (new ConflictException('Business rule violation.'))->withErrors([...]);
 */
class ConflictException extends DirectiveException
{
    /** @var array<array<string, string>> */
    private array $errors = [];

    public function httpStatus(): int
    {
        return 409;
    }

    /**
     * Return a new instance with the given errors attached.
     * Creates a fresh instance via `new static()` so the original object is unmodified.
     *
     * NOTE: PHP 8.4 forbids cloning exceptions (`Exception::__clone()` is final),
     *       so we use `new static()` instead.
     *
     * @param array<array<string, string>> $errors
     */
    public function withErrors(array $errors): static
    {
        // Subclasses (e.g. BadRequestException) share the same RuntimeException constructor,
        // so new static() is safe here. phpstan cannot verify this statically, hence the ignore.
        /** @phpstan-ignore new.static */
        $new         = new static($this->getMessage(), $this->getCode(), $this->getPrevious());
        $new->errors = $errors;

        return $new;
    }

    /** @return array<array<string, string>> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

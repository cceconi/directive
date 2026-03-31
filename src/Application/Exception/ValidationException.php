<?php

declare(strict_types=1);

namespace Directive\Application\Exception;

final class ValidationException extends AbstractDomainException
{
    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $context
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        array $context = [],
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

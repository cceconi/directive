<?php

declare(strict_types=1);

namespace Directive\Web\Constraints;

use Directive\Web\UploadedFile\FileInfo;
use Directive\Web\UploadedFile\ValidatorException;
use Directive\Web\UploadedFile\ValidatorInterface;

/**
 * Constraint that delegates to an ordered list of ValidatorInterface instances.
 *
 * All validators are executed; errors are collected separately via getErrors().
 */
final class FileConstraint extends GenericConstraint
{
    /** @var ValidatorInterface[] */
    private readonly array $validators;

    /** @var string[] */
    private array $errors = [];

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
        parent::__construct(false);

        $this->validators = $validators;
    }

    public function checkType(mixed $value): bool
    {
        return $value instanceof FileInfo;
    }

    public function checkValue(mixed $value): bool
    {
        if (!$value instanceof FileInfo) {
            return false;
        }

        $this->errors = [];
        $valid        = true;

        foreach ($this->validators as $validator) {
            try {
                $validator->validate($value);
            } catch (ValidatorException $e) {
                $this->errors[] = $e->getMessage();
                $valid          = false;
            }
        }

        return $valid;
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function __toString(): string
    {
        return sprintf('file validators=%d', count($this->validators));
    }
}

<?php

declare(strict_types=1);

namespace Directive\Http\Input;

/**
 * Contract for typed input wrappers.
 *
 * Each InterfaceData wraps one request input field:
 *   - stores the original raw value
 *   - produces a cleaned/sanitized value (once, lazily)
 *   - validates against a constraint
 *   - carries an error label for reporting
 */
interface InterfaceDataInterface
{
    public function hydrate(mixed $rawData): void;

    public function getOriginalValue(): mixed;

    public function getCleanedValue(): mixed;

    /**
     * The value to use in business logic (cleaned value if valid, null otherwise).
     */
    public function getResultValue(): mixed;

    public function validate(): bool;

    public function getErrorLabel(): string;
}

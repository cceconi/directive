<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

/**
 * Contract for any class that contributes configuration keys to the shared dictionary.
 *
 * Implementations declare their keys by calling required() / optional() on the
 * provided Configuration instance. The framework calls define() in a deterministic
 * order: framework service configs first, then the application's own config class
 * last — so application declarations always win on key conflicts.
 */
interface ConfigProviderInterface
{
    public function define(Configuration $config): void;
}

<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

/**
 * Contract every user-defined Configuration class must satisfy.
 *
 * Concrete implementation lives in Epic 8. This interface is kept minimal
 * so AbstractApplication can depend on it without pulling in the full
 * configuration surface area.
 */
interface ConfigurationInterface
{
    /**
     * Assert all required parameters are present and valid.
     * Throws ConfigurationException on any missing/invalid value.
     */
    public function validate(): void;

    /**
     * Return the log directory used by all loggers.
     */
    public function getLogDir(): string;

    /**
     * Return the configured runtime logger name
     * ('webapp' or 'console' by convention).
     */
    public function getRuntimeLoggerName(): string;

    /**
     * Read a dot-notation configuration key.
     *
     * @param mixed $default Returned when the key is absent.
     */
    public function get(string $key, mixed $default = null): mixed;
}

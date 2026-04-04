<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

use Directive\Exception\ConfigurationException;

/**
 * Base class for application-level configuration backed by environment variables.
 *
 * Subclasses call required() / optional() inside define() to declare their
 * keys, then call audit() once at boot time to validate and resolve all values.
 */
abstract class AbstractConfiguration
{
    /** @var array<string, array{type: string, required: bool, default: mixed, allowed: list<string>}> */
    private array $definitions = [];

    /** @var array<string, mixed> */
    private array $resolved = [];

    /** @var array<string, string> */
    private array $sources = [];

    public function __construct()
    {
        $this->define();
    }

    // ------------------------------------------------------------------
    // Contract for subclasses
    // ------------------------------------------------------------------

    /**
     * Declare configuration keys by calling required() and optional().
     */
    abstract protected function define(): void;

    // ------------------------------------------------------------------
    // Declaration helpers
    // ------------------------------------------------------------------

    /**
     * Declare a required environment variable.
     *
     * @param list<string> $allowed Whitelist of accepted raw string values (empty = any)
     */
    protected function required(string $key, string $type = 'string', array $allowed = []): void
    {
        $this->definitions[$key] = [
            'type'     => $type,
            'required' => true,
            'default'  => null,
            'allowed'  => $allowed,
        ];
    }

    /**
     * Declare an optional environment variable with a fallback default.
     *
     * @param list<string> $allowed Whitelist of accepted raw string values (empty = any)
     */
    protected function optional(string $key, mixed $default, string $type = 'string', array $allowed = []): void
    {
        $this->definitions[$key] = [
            'type'     => $type,
            'required' => false,
            'default'  => $default,
            'allowed'  => $allowed,
        ];
    }

    // ------------------------------------------------------------------
    // Boot-time validation
    // ------------------------------------------------------------------

    /**
     * Pre-populate resolved values from a compiled config cache.
     *
     * Keys present in $values are marked as resolved from 'cache' and will
     * be skipped by audit() — no $_ENV lookup, no allowed-list check.
     * Sensitive variables are never written to the cache by config:compile,
     * so they will still be validated from $_ENV during audit().
     *
     * Only keys declared in define() are accepted; unknown keys are ignored.
     *
     * @param array<string, mixed> $values
     */
    public function loadCache(array $values): void
    {
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $this->definitions)) {
                $this->resolved[$key] = $value;
                $this->sources[$key]  = 'cache';
            }
        }
    }

    /**
     * Validate all declared keys against $_ENV and resolve their values.
     * Keys already resolved via loadCache() are skipped.
     *
     * @throws ConfigurationException when required keys are missing or values are not in their allowed list.
     */
    public function audit(): void
    {
        $errors = [];

        foreach ($this->definitions as $key => $def) {
            // Already resolved from compiled cache — skip $_ENV lookup.
            if (array_key_exists($key, $this->resolved)) {
                continue;
            }

            $raw = $_ENV[$key] ?? null;

            if ($raw === null) {
                if ($def['required']) {
                    $errors[] = sprintf('Missing required variable: %s', $key);
                    continue;
                }

                $this->resolved[$key] = $def['default'];
                $this->sources[$key]  = 'default';
                continue;
            }

            $rawStr = (string) $raw;

            if ($def['allowed'] !== [] && !in_array($rawStr, $def['allowed'], strict: true)) {
                $errors[] = sprintf(
                    'Variable %s has value "%s" which is not in allowed list [%s]',
                    $key,
                    $rawStr,
                    implode(', ', $def['allowed']),
                );
                continue;
            }

            $this->resolved[$key] = $this->cast($rawStr, $def['type']);
            $this->sources[$key]  = ConfigSourceTracker::getSource($key) ?? 'default';
        }

        if ($errors !== []) {
            throw new ConfigurationException(implode('; ', $errors));
        }
    }

    // ------------------------------------------------------------------
    // Runtime accessors
    // ------------------------------------------------------------------

    /**
     * Get the resolved value of a declared configuration key.
     *
     * @throws ConfigurationException when the key was never declared.
     */
    public function get(string $key): mixed
    {
        if (!array_key_exists($key, $this->definitions)) {
            throw new ConfigurationException(sprintf('Configuration key "%s" is not declared.', $key));
        }

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        return $this->definitions[$key]['default'];
    }

    /**
     * Return all resolved values.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        return $this->resolved;
    }

    /**
     * Return the tracked source origin for a resolved variable.
     *
     * Possible values: '.env', '.env.local', 'system', 'default'.
     *
     * @throws ConfigurationException when the key is not declared or audit() has not been called yet.
     */
    public function getSource(string $key): string
    {
        if (!array_key_exists($key, $this->definitions)) {
            throw new ConfigurationException(sprintf('Configuration key "%s" is not declared.', $key));
        }

        if (!array_key_exists($key, $this->sources)) {
            throw new ConfigurationException(
                sprintf('Source for "%s" is not available — has audit() been called?', $key),
            );
        }

        return $this->sources[$key];
    }

    /**
     * Return the raw definition metadata for all declared keys.
     *
     * @return array<string, array{type: string, required: bool, default: mixed, allowed: list<string>}>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }

    // ------------------------------------------------------------------
    // Internals
    // ------------------------------------------------------------------

    private function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'int'   => (int) $value,
            'float' => (float) $value,
            'bool'  => in_array(strtolower($value), ['1', 'true', 'yes'], strict: true),
            default => $value,
        };
    }
}

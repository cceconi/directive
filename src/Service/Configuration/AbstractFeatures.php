<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

use Directive\Exception\ConfigurationException;

/**
 * Base class for feature-flag sets backed by environment variables.
 *
 * Subclasses call feature() inside define() to register flags.
 * Each flag maps to a boolean env variable.
 */
abstract class AbstractFeatures
{
    /** @var array<string, array{envKey: string, default: bool}> */
    private array $definitions = [];

    public function __construct()
    {
        $this->define();
    }

    // ------------------------------------------------------------------
    // Contract for subclasses
    // ------------------------------------------------------------------

    /**
     * Register feature flags by calling feature().
     */
    abstract protected function define(): void;

    // ------------------------------------------------------------------
    // Declaration helper
    // ------------------------------------------------------------------

    /**
     * Register a feature flag.
     *
     * @param string $name    Logical flag name used by application code.
     * @param string $envKey  Environment variable key to read.
     * @param bool   $default Default state when the variable is absent.
     */
    protected function feature(string $name, string $envKey, bool $default = false): void
    {
        $this->definitions[$name] = ['envKey' => $envKey, 'default' => $default];
    }

    // ------------------------------------------------------------------
    // Runtime accessors
    // ------------------------------------------------------------------

    /**
     * Return whether a feature flag is currently enabled.
     *
     * @throws ConfigurationException when the flag name was never declared.
     */
    public function isEnabled(string $name): bool
    {
        if (!array_key_exists($name, $this->definitions)) {
            throw new ConfigurationException(sprintf('Feature flag "%s" is not declared.', $name));
        }

        $def = $this->definitions[$name];
        $raw = $_ENV[$def['envKey']] ?? null;

        if ($raw === null) {
            return $def['default'];
        }

        return in_array(strtolower((string) $raw), ['1', 'true', 'yes'], strict: true);
    }

    /**
     * Return all feature flags with their current state.
     *
     * @return array<string, bool>
     */
    public function getAll(): array
    {
        $result = [];

        foreach (array_keys($this->definitions) as $name) {
            $result[$name] = $this->isEnabled($name);
        }

        return $result;
    }

    /**
     * Return the raw definition metadata for all declared flags.
     *
     * @return array<string, array{envKey: string, default: bool}>
     */
    public function getDefinitions(): array
    {
        return $this->definitions;
    }
}

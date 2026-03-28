<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

use Symfony\Component\Dotenv\Dotenv;

/**
 * Static registry that tracks the origin of each environment variable.
 *
 * Usage in the application bootstrap -- BEFORE calling audit() on any config:
 *
 *   ConfigSourceTracker::snapshotSystemVars();
 *   ConfigSourceTracker::loadTracked($dotenv, basePath: dirname(__DIR__));
 *
 * AbstractConfiguration::audit() then calls ConfigSourceTracker::getSource()
 * for each resolved variable to record its origin.
 *
 * Possible source labels:
 *   'system'     -- variable was present in $_ENV before dotenv loading
 *   '.env'       -- variable was introduced or changed by loading .env
 *   '.env.local' -- variable was introduced or changed by loading .env.local
 *   'default'    -- variable had no $_ENV entry; audit() used the coded default value
 */
final class ConfigSourceTracker
{
    /** @var array<string, string> */
    private static array $sourceMap = [];

    private function __construct() {}

    /**
     * Record all variables currently in $_ENV as 'system'.
     * Must be called BEFORE any dotenv file is loaded.
     */
    public static function snapshotSystemVars(): void
    {
        foreach (array_keys($_ENV) as $key) {
            if (!array_key_exists($key, self::$sourceMap)) {
                self::$sourceMap[(string) $key] = 'system';
            }
        }
    }

    /**
     * Load dotenv files one by one and record which file introduced or changed each variable.
     *
     * Loads (in order, if they exist):
     *   - $basePath/.env
     *   - $basePath/.env.local
     *
     * A variable that already exists in $_ENV (system var) won't be changed by symfony/dotenv
     * by default, so it retains its 'system' source label.
     */
    public static function loadTracked(Dotenv $dotenv, string $basePath): void
    {
        $files = [
            '.env'       => $basePath . '/.env',
            '.env.local' => $basePath . '/.env.local',
        ];

        foreach ($files as $sourceLabel => $filePath) {
            if (!is_file($filePath)) {
                continue;
            }

            $snapshot = $_ENV;

            $dotenv->load($filePath);

            foreach ($_ENV as $key => $value) {
                $keyStr = (string) $key;
                if (!array_key_exists($keyStr, $snapshot) || $snapshot[$keyStr] !== $value) {
                    self::$sourceMap[$keyStr] = $sourceLabel;
                }
            }
        }
    }

    /**
     * Return the tracked source for a variable, or null if unknown.
     * 'default' is set by AbstractConfiguration::audit() when no $_ENV entry exists.
     */
    public static function getSource(string $key): ?string
    {
        return self::$sourceMap[$key] ?? null;
    }

    /**
     * Clear the registry. Intended for use in tests.
     */
    public static function reset(): void
    {
        self::$sourceMap = [];
    }
}

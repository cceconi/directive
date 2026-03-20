<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

use Directive\Exception\ConfigurationException;

/**
 * Base configuration class.
 *
 * Lifecycle (driven by AbstractApplication::setConfig):
 *   1. new MyConfig()       — defaults registered via setDefault(), requiredParams declared.
 *   2. ->setParams()        — user sets all app/env parameters.
 *   3. ->validate()         — checks all required params are present.
 */
abstract class Configuration implements ConfigurationInterface
{
    /** @var array<string, string> key => human-readable description */
    private array $requiredParams = [];

    /** @var array<string, mixed> */
    private array $params = [];

    private string $appCode = '';
    private string $envCode = '';

    public function __construct()
    {
        $this->addRequiredParams([
            'app.name'    => 'Application name',
            'app.code'    => 'Application code (unique identifier)',
            'app.charset' => 'Charset (e.g. utf-8)',
            'app.basepath' => 'Application root directory',
        ]);
        $this->addRequiredParams([
            'env.name'     => 'Environment name',
            'env.code'     => 'Environment code (unique identifier)',
            'env.platform' => 'Environment platform info',
            'env.timezone' => 'Timezone (e.g. Europe/Paris)',
            'env.debug'    => 'Boolean to activate debug mode',
            'env.log.path' => 'Directory where log files are written',
            'env.host'     => 'DNS of your app (e.g. my.app.com)',
            'env.url'      => 'URL of your app (e.g. https://my.app.com)',
            'env.app.secretkey'        => 'UUID secret key for app-management API',
            'env.maintenance.filename' => 'Path to the maintenance JSON file',
            'env.appinfo.filename'     => 'Path to the appinfo JSON file',
            'env.security.webuser'     => 'FQCN of the WebUser implementation',
            'env.security.cors'        => 'Array of allowed CORS origins',
            'env.security.route.csrf-excluded' => 'Routes excluded from CSRF check',
            'env.security.http.secure' => 'Require HTTPS (true/false)',
        ]);

        $this->setDefault();
    }

    // ── Abstract hooks ───────────────────────────────────────────────────────

    /** User sets all app/env parameters here. */
    abstract public function setParams(): void;

    // ── ConfigurationInterface ───────────────────────────────────────────────

    public function validate(): void
    {
        $missing = array_diff(
            array_keys($this->requiredParams),
            array_keys($this->params),
        );

        if ($missing !== []) {
            throw new ConfigurationException(
                'Missing configuration params: ' . implode(', ', $missing),
            );
        }

        $this->appCode = (string) $this->get('app.code');
        $this->envCode = (string) $this->get('env.code');
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getLogDir(): string
    {
        return (string) $this->get('env.log.path', sys_get_temp_dir());
    }

    public function getRuntimeLoggerName(): string
    {
        return (string) $this->get('env.name', 'webapp');
    }

    // ── Additional convenience getters ───────────────────────────────────────

    public function getAppCode(): string
    {
        return $this->appCode;
    }

    public function getEnvCode(): string
    {
        return $this->envCode;
    }

    // ── Protected helpers for subclasses ─────────────────────────────────────

    /**
     * Register required parameters with a human-readable description.
     *
     * @param array<string, string> $params
     */
    public function addRequiredParams(array $params): void
    {
        $this->requiredParams = array_merge($this->requiredParams, $params);
    }

    /**
     * Set a configuration value.
     *
     * @throws ConfigurationException when key is already set and $force is false.
     */
    protected function set(string $key, mixed $value, bool $force = false): void
    {
        if (!$force && array_key_exists($key, $this->params)) {
            throw new ConfigurationException("Configuration param '{$key}' is already set.");
        }
        $this->params[$key] = $value;
    }

    /**
     * Load additional parameters from an external JSON file.
     * Each entry may be a plain value or an array {'value': ..., 'force': bool}.
     *
     * @throws ConfigurationException on parse error.
     */
    protected function loadExternalConfiguration(string $confPath): void
    {
        $parsed = $this->parse($confPath);

        foreach ($parsed as $key => $item) {
            $value = $item;
            $force = false;
            if (is_array($item) && array_key_exists('value', $item)) {
                $value = $item['value'];
                $force = (bool) ($item['force'] ?? false);
            }
            $this->set((string) $key, $value, $force);
        }
    }

    /**
     * Parse a JSON configuration file.
     *
     * @return array<string, mixed>
     * @throws ConfigurationException
     */
    protected function parse(string $confPath): array
    {
        if (!is_file($confPath)) {
            throw new ConfigurationException("Configuration file '{$confPath}' not found.");
        }
        $json = file_get_contents($confPath);
        if ($json === false) {
            throw new ConfigurationException("Cannot read configuration file '{$confPath}'.");
        }
        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new ConfigurationException(
                "Invalid JSON in '{$confPath}': " . json_last_error_msg(),
            );
        }
        return $data;
    }

    // ── Defaults ─────────────────────────────────────────────────────────────

    protected function setDefault(): void
    {
        $this->set('env.response.compress', false);
        $this->set('env.security.http.secure', true);
        $this->set('env.security.http.relaxed', []);
        $this->set('env.security.cookie.httponly', true);
        $this->set('env.security.cookie.samesite', 'strict');
        $this->set('env.security.cookie.domain', '');
        $this->set('env.security.cors', []);
        $this->set('env.security.route.csrf-excluded', []);
        $this->set('env.client.headers.list', []);
        $this->set('env.antivirus.name', 'clamav');
        $this->set('env.antivirus.host', '127.0.0.1');
        $this->set('env.antivirus.port', 3310);
        $this->set('env.antivirus.timeout', 5);
    }
}

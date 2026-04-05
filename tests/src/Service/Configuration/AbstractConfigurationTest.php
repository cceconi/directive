<?php

declare(strict_types=1);

use Directive\Exception\ConfigurationException;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

// ── Stub ─────────────────────────────────────────────────────────────────────

final class StubConfigProvider implements ConfigProviderInterface
{
    public function define(Configuration $config): void
    {
        $config->required('APP_ENV', 'string', ['development', 'staging', 'production']);
        $config->optional('APP_ENV_PROD_NAME', 'production', 'string');
        $config->required('DB_PORT', 'int');
        $config->optional('DEBUG', false, 'bool');
        $config->optional('APP_NAME', 'Directive', 'string');
    }
}

// ── Helper ────────────────────────────────────────────────────────────────────

function makeStubConfig(): Configuration
{
    $config = new Configuration();
    (new StubConfigProvider())->define($config);
    return $config;
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('Configuration', function (): void {

    beforeEach(function (): void {
        // Clean env before each test
        unset($_ENV['APP_ENV'], $_ENV['DB_PORT'], $_ENV['DEBUG'], $_ENV['APP_NAME']);
    });

    it('resolves required keys from $_ENV after audit', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        $config->audit();
        expect($config->get('APP_ENV'))->toBe('production');
        expect($config->get('DB_PORT'))->toBe(5432);
    });

    it('returns default for optional key when env var is absent', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        $config->audit();
        expect($config->get('DEBUG'))->toBe(false);
        expect($config->get('APP_NAME'))->toBe('Directive');
    });

    it('throws ConfigurationException when required key is missing', function (): void {
        // DB_PORT is not set
        $_ENV['APP_ENV'] = 'production';
        $config = makeStubConfig();
        expect(fn() => $config->audit())->toThrow(ConfigurationException::class);
    });

    it('throws ConfigurationException when value is not in allowed list', function (): void {
        $_ENV['APP_ENV'] = 'unknown';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        expect(fn() => $config->audit())->toThrow(ConfigurationException::class);
    });

    it('throws ConfigurationException when getting undeclared key', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        $config->audit();
        expect(fn() => $config->get('UNDECLARED'))->toThrow(ConfigurationException::class);
    });

    it('casts bool env var correctly', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DEBUG']   = 'true';
        $config = makeStubConfig();
        $config->audit();
        expect($config->get('DEBUG'))->toBe(true);
    });

    it('returns all resolved values via getAll()', function (): void {
        $_ENV['APP_ENV'] = 'staging';
        $_ENV['DB_PORT'] = '3306';
        $config = makeStubConfig();
        $config->audit();
        $all = $config->getAll();
        expect($all)->toHaveKey('APP_ENV');
        expect($all)->toHaveKey('DB_PORT');
    });

    it('returns definitions via getDefinitions()', function (): void {
        $config = makeStubConfig();
        $defs = $config->getDefinitions();
        expect($defs)->toHaveKey('APP_ENV');
        expect($defs['APP_ENV']['required'])->toBe(true);
        expect($defs['APP_ENV']['type'])->toBe('string');
    });

    it('loadCache() pre-populates resolved values and audit() skips them', function (): void {
        // DB_PORT is required but absent from $_ENV — cache provides it
        $_ENV['APP_ENV'] = 'production';
        $config = makeStubConfig();
        $config->loadCache(['APP_ENV' => 'staging', 'DB_PORT' => 5432]);
        $config->audit();
        // Cache value wins over $_ENV
        expect($config->get('APP_ENV'))->toBe('staging');
        expect($config->get('DB_PORT'))->toBe(5432);
    });

    it('loadCache() source is tracked as cache', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        $config->loadCache(['APP_ENV' => 'staging']);
        $config->audit();
        expect($config->getSource('APP_ENV'))->toBe('cache');
        // DB_PORT resolved from $_ENV, not cache
        expect($config->getSource('DB_PORT'))->not->toBe('cache');
    });

    it('loadCache() ignores unknown keys silently', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = makeStubConfig();
        $config->loadCache(['UNKNOWN_KEY' => 'value']);
        $config->audit();
        expect($config->get('APP_ENV'))->toBe('production');
    });

    it('last provider wins on key conflict', function (): void {
        $config = new Configuration();
        $config->optional('APP_ENV', 'dev', 'string');          // framework default
        $config->optional('APP_ENV', 'production', 'string');   // app override
        $config->audit();
        expect($config->get('APP_ENV'))->toBe('production');
    });
});

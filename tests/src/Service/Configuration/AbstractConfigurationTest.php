<?php

declare(strict_types=1);

use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Exception\ConfigurationException;

// ── Stub ─────────────────────────────────────────────────────────────────────

final class StubConfiguration extends AbstractConfiguration
{
    protected function define(): void
    {
        $this->required('APP_ENV', 'string', ['development', 'staging', 'production']);
        $this->required('DB_PORT', 'int');
        $this->optional('DEBUG', false, 'bool');
        $this->optional('APP_NAME', 'Directive', 'string');
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('AbstractConfiguration', function (): void {

    beforeEach(function (): void {
        // Clean env before each test
        unset($_ENV['APP_ENV'], $_ENV['DB_PORT'], $_ENV['DEBUG'], $_ENV['APP_NAME']);
    });

    it('resolves required keys from $_ENV after audit', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = new StubConfiguration();
        $config->audit();
        expect($config->get('APP_ENV'))->toBe('production');
        expect($config->get('DB_PORT'))->toBe(5432);
    });

    it('returns default for optional key when env var is absent', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = new StubConfiguration();
        $config->audit();
        expect($config->get('DEBUG'))->toBe(false);
        expect($config->get('APP_NAME'))->toBe('Directive');
    });

    it('throws ConfigurationException when required key is missing', function (): void {
        // DB_PORT is not set
        $_ENV['APP_ENV'] = 'production';
        $config = new StubConfiguration();
        expect(fn () => $config->audit())->toThrow(ConfigurationException::class);
    });

    it('throws ConfigurationException when value is not in allowed list', function (): void {
        $_ENV['APP_ENV'] = 'unknown';
        $_ENV['DB_PORT'] = '5432';
        $config = new StubConfiguration();
        expect(fn () => $config->audit())->toThrow(ConfigurationException::class);
    });

    it('throws ConfigurationException when getting undeclared key', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $config = new StubConfiguration();
        $config->audit();
        expect(fn () => $config->get('UNDECLARED'))->toThrow(ConfigurationException::class);
    });

    it('casts bool env var correctly', function (): void {
        $_ENV['APP_ENV'] = 'production';
        $_ENV['DB_PORT'] = '5432';
        $_ENV['DEBUG']   = 'true';
        $config = new StubConfiguration();
        $config->audit();
        expect($config->get('DEBUG'))->toBe(true);
    });

    it('returns all resolved values via getAll()', function (): void {
        $_ENV['APP_ENV'] = 'staging';
        $_ENV['DB_PORT'] = '3306';
        $config = new StubConfiguration();
        $config->audit();
        $all = $config->getAll();
        expect($all)->toHaveKey('APP_ENV');
        expect($all)->toHaveKey('DB_PORT');
    });

    it('returns definitions via getDefinitions()', function (): void {
        $config = new StubConfiguration();
        $defs = $config->getDefinitions();
        expect($defs)->toHaveKey('APP_ENV');
        expect($defs['APP_ENV']['required'])->toBe(true);
        expect($defs['APP_ENV']['type'])->toBe('string');
    });
});

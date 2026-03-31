<?php

declare(strict_types=1);

use Directive\Service\Configuration\AbstractFeatures;
use Directive\Service\Configuration\DirectiveFeatures;
use Directive\Exception\ConfigurationException;

describe('DirectiveFeatures', function (): void {

    beforeEach(function (): void {
        unset(
            $_ENV['DIRECTIVE_RATE_LIMIT_ENABLED'],
            $_ENV['DIRECTIVE_DEBUG_LOGGING'],
        );
    });

    it('extends AbstractFeatures', function (): void {
        expect(new DirectiveFeatures())->toBeInstanceOf(AbstractFeatures::class);
    });

    it('rate_limit is enabled by default', function (): void {
        $features = new DirectiveFeatures();
        expect($features->isEnabled('rate_limit'))->toBeTrue();
    });

    it('rate_limit can be disabled via env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_ENABLED'] = '0';
        $features = new DirectiveFeatures();
        expect($features->isEnabled('rate_limit'))->toBeFalse();
    });

    it('rate_limit can be explicitly enabled via env var', function (): void {
        $_ENV['DIRECTIVE_RATE_LIMIT_ENABLED'] = '1';
        $features = new DirectiveFeatures();
        expect($features->isEnabled('rate_limit'))->toBeTrue();
    });

    it('throws ConfigurationException for unknown flag', function (): void {
        $features = new DirectiveFeatures();
        expect(fn () => $features->isEnabled('unknown_flag'))->toThrow(ConfigurationException::class);
    });

    it('getAll includes rate_limit flag', function (): void {
        $features = new DirectiveFeatures();
        $all      = $features->getAll();

        expect($all)->toHaveKey('rate_limit');
    });

    it('debug_logging is disabled by default', function (): void {
        $features = new DirectiveFeatures();
        expect($features->isEnabled('debug_logging'))->toBeFalse();
    });

    it('debug_logging can be enabled via env var', function (): void {
        $_ENV['DIRECTIVE_DEBUG_LOGGING'] = '1';
        $features = new DirectiveFeatures();
        expect($features->isEnabled('debug_logging'))->toBeTrue();
    });

    it('getAll includes debug_logging flag', function (): void {
        $features = new DirectiveFeatures();
        $all      = $features->getAll();

        expect($all)->toHaveKey('debug_logging');
    });
});

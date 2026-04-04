<?php

declare(strict_types=1);

use Directive\Exception\ConfigurationException;
use Directive\Service\Configuration\AbstractFeatures;

// ── Stub ─────────────────────────────────────────────────────────────────────

final class StubFeatures extends AbstractFeatures
{
    protected function define(): void
    {
        $this->feature('dark_mode', 'FEATURE_DARK_MODE', false);
        $this->feature('beta_ui', 'FEATURE_BETA_UI', true);
        $this->feature('maintenance', 'FEATURE_MAINTENANCE', false);
    }
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('AbstractFeatures', function (): void {

    beforeEach(function (): void {
        unset($_ENV['FEATURE_DARK_MODE'], $_ENV['FEATURE_BETA_UI'], $_ENV['FEATURE_MAINTENANCE']);
    });

    it('returns default when env var is absent', function (): void {
        $features = new StubFeatures();
        expect($features->isEnabled('dark_mode'))->toBe(false);
        expect($features->isEnabled('beta_ui'))->toBe(true);
    });

    it('returns true when env var is "1"', function (): void {
        $_ENV['FEATURE_DARK_MODE'] = '1';
        $features = new StubFeatures();
        expect($features->isEnabled('dark_mode'))->toBe(true);
    });

    it('returns false when env var is "false"', function (): void {
        $_ENV['FEATURE_BETA_UI'] = 'false';
        $features = new StubFeatures();
        expect($features->isEnabled('beta_ui'))->toBe(false);
    });

    it('returns true when env var is "yes"', function (): void {
        $_ENV['FEATURE_MAINTENANCE'] = 'yes';
        $features = new StubFeatures();
        expect($features->isEnabled('maintenance'))->toBe(true);
    });

    it('throws ConfigurationException for undeclared flag', function (): void {
        $features = new StubFeatures();
        expect(fn() => $features->isEnabled('not_declared'))->toThrow(ConfigurationException::class);
    });

    it('returns all flags via getAll()', function (): void {
        $features = new StubFeatures();
        $all = $features->getAll();
        expect($all)->toHaveKey('dark_mode');
        expect($all)->toHaveKey('beta_ui');
        expect($all)->toHaveKey('maintenance');
    });

    it('returns definitions via getDefinitions()', function (): void {
        $features = new StubFeatures();
        $defs = $features->getDefinitions();
        expect($defs)->toHaveKey('dark_mode');
        expect($defs['dark_mode']['envKey'])->toBe('FEATURE_DARK_MODE');
        expect($defs['dark_mode']['default'])->toBe(false);
    });
});

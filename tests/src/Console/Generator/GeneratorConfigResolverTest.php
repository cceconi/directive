<?php

declare(strict_types=1);

use Directive\Console\Generator\GeneratorConfigResolver;

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-config-test-' . uniqid();
    mkdir($this->cwd);
    chdir($this->cwd);
});

afterEach(function (): void {
    array_map('unlink', glob($this->cwd . '/*') ?: []);
    rmdir($this->cwd);
});

describe('GeneratorConfigResolver', function (): void {

    it('resolves from directive-dev.json', function (): void {
        file_put_contents(
            $this->cwd . '/directive-dev.json',
            json_encode(['namespace' => 'App', 'src' => 'src']),
        );

        $result = new GeneratorConfigResolver()->resolve();

        expect($result)->toBe(['namespace' => 'App', 'src' => 'src']);
    });

    it('resolves from composer.json PSR-4 when no directive-dev.json', function (): void {
        file_put_contents(
            $this->cwd . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['MyApp\\' => 'src/']]]),
        );

        $result = new GeneratorConfigResolver()->resolve();

        expect($result)->toBe(['namespace' => 'MyApp', 'src' => 'src']);
    });

    it('prefers directive-dev.json over composer.json', function (): void {
        file_put_contents(
            $this->cwd . '/directive-dev.json',
            json_encode(['namespace' => 'DevApp', 'src' => 'app']),
        );
        file_put_contents(
            $this->cwd . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['ComposerApp\\' => 'src/']]]),
        );

        $result = new GeneratorConfigResolver()->resolve();

        expect($result)->toBe(['namespace' => 'DevApp', 'src' => 'app']);
    });

    it('returns null when neither file exists', function (): void {
        $result = new GeneratorConfigResolver()->resolve();

        expect($result)->toBeNull();
    });

});

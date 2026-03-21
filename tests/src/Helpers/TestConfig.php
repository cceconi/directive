<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Configuration\ConfigurationInterface;

/** Minimal configuration for tests. */
final class TestConfig implements ConfigurationInterface
{
    /** @var array<string, mixed> */
    private array $params = [
        'app.name'        => 'DirectiveTestApp',
        'app.version'     => '3.0.0',
        'app.description' => 'Test application',
        'app.code'        => 'apisy-test',
        'log.dir'         => '/tmp',
        'env.files.tmpdir' => '/tmp/apisy-test-uploads',
    ];

    public function setParams(): void {}

    public function validate(): void {}

    public function getLogDir(): string
    {
        return '/tmp';
    }

    public function getRuntimeLoggerName(): string
    {
        return 'console';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }
}

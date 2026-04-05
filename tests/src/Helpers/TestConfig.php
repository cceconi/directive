<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Configuration\AbstractConfiguration;

/** Minimal configuration for tests — all keys are optional with sensible defaults. */
final class TestConfig extends AbstractConfiguration
{
    protected function define(): void
    {
        $this->optional('APP_CODE', 'directive-test', 'string');
        $this->optional('APP_NAME', 'DirectiveTestApp', 'string');
        $this->optional('APP_ENV', 'test', 'string');
        $this->optional('APP_VERSION', '3.0.0', 'string');
        $this->optional('APP_DESCRIPTION', 'Test application', 'string');
        $this->optional('APP_URL', '', 'string');
        $this->optional('LOG_PATH', '/tmp', 'string');
        $this->optional('UPLOAD_TMPDIR', '/tmp/directive-test-uploads', 'string');
    }
}

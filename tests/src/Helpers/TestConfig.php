<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Configuration\AbstractConfiguration;

/** Minimal configuration for tests — all keys are optional with sensible defaults. */
final class TestConfig extends AbstractConfiguration
{
    protected function define(): void
    {
        $this->optional('DIRECTIVE_APP_CODE', 'directive-test', 'string');
        $this->optional('DIRECTIVE_APP_NAME', 'DirectiveTestApp', 'string');
        $this->optional('DIRECTIVE_APP_VERSION', '3.0.0', 'string');
        $this->optional('DIRECTIVE_APP_DESCRIPTION', 'Test application', 'string');
        $this->optional('DIRECTIVE_APP_URL', '', 'string');
        $this->optional('DIRECTIVE_LOG_PATH', '/tmp', 'string');
        $this->optional('DIRECTIVE_ENV_CODE', 'test', 'string');
        $this->optional('DIRECTIVE_UPLOAD_TMPDIR', '/tmp/directive-test-uploads', 'string');
    }
}

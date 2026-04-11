<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;

/** Minimal configuration for tests — all keys are optional with sensible defaults. */
final class TestConfig implements ConfigProviderInterface
{
    public function define(Configuration $config): void
    {
        $config->optional('APP_ENV', 'test', 'string');
        $config->optional('APP_DESCRIPTION', 'Test application', 'string');
        $config->optional('APP_URL', '', 'string');
        $config->optional('LOG_PATH', '/tmp', 'string');
        $config->optional('UPLOAD_TMPDIR', '/tmp/directive-test-uploads', 'string');
    }
}

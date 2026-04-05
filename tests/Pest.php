<?php

declare(strict_types=1);

use Directive\Service\Configuration\Configuration;
use Tests\Helpers\TestConfig;

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
*/

uses(
    Tests\Helpers\DirectiveHttpTestCase::class,
)->in(__DIR__ . '/src');

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
*/

/**
 * Create a Configuration pre-populated with TestConfig's declarations.
 * Use in tests that need a shared config dict without a full app boot.
 */
function makeTestConfig(): Configuration
{
    $config = new Configuration();
    (new TestConfig())->define($config);
    return $config;
}

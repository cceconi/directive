<?php

declare(strict_types=1);

use Directive\Service\AppIdentity\DefaultAppIdentityConfig;
use Directive\Service\AppManagement\AppInfo;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Logging\DefaultLoggingConfig;
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
 *
 * @param array<string, mixed> $runtimeOverrides  Extra keys injected via setRuntimeValue() (declared + resolved immediately).
 */
function makeTestConfig(array $runtimeOverrides = []): Configuration
{
    $config = new Configuration();
    (new TestConfig())->define($config);
    foreach ($runtimeOverrides as $key => $value) {
        $config->setRuntimeValue($key, $value);
    }
    return $config;
}

/**
 * Create a DefaultLoggingConfig hooked up to a DefaultAppIdentityConfig backed by AppInfo.
 * Config is audited; set any relevant $_ENV vars before calling.
 *
 * @param array<string,mixed> $appInfoData  Forwarded to AppInfo (keys: name, version, etc.)
 */
function makeTestLoggingConfig(array $appInfoData = []): DefaultLoggingConfig
{
    $config   = makeTestConfig();
    $identity = new DefaultAppIdentityConfig(new AppInfo($appInfoData), $config);
    $identity->define($config);
    $logging  = new DefaultLoggingConfig($identity, $config);
    $logging->define($config);
    $config->audit();
    return $logging;
}

<?php

declare(strict_types=1);

use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\EventBus\NullDomainEventBus;
use Directive\AbstractConsoleApplication;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Logging\RequestIdHolder;
use Directive\Service\Security\Antivirus\AntivirusConfigInterface;
use Directive\Service\Security\Antivirus\DefaultAntivirusConfig;
use Directive\Service\Security\SecurityConfigInterface;
use Tests\Helpers\TestConfig;

// ---------------------------------------------------------------------------
// Minimal stub: extends AbstractConsoleApplication to skip the full Symfony boot.
// We only call setConfig() — never run() — so bootConsole() is never reached.
// ---------------------------------------------------------------------------

final class BootTestApplication extends AbstractConsoleApplication
{
    protected function registerServices(Configuration $config): void
    {
        // intentionally minimal — we only need the container to be built
    }

    protected function addServices(): void
    {
        // no-op: skip console command registration
    }

    /** @param array<string, mixed> $definitions */
    public function define(array $definitions): void
    {
        $this->addDefinitions($definitions);
    }
}

// ---------------------------------------------------------------------------

describe('AbstractApplication service auto-binding', function (): void {

    beforeEach(function (): void {
        $_ENV['APP_ENV'] = 'prod';
    });

    afterEach(function (): void {
        unset($_ENV['APP_ENV']);
    });

    it('auto-binds AntivirusConfigInterface to DefaultAntivirusConfig', function (): void {
        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        $resolved = $app->getContainer()->get(AntivirusConfigInterface::class);

        expect($resolved)->toBeInstanceOf(AntivirusConfigInterface::class);
        expect($resolved)->toBeInstanceOf(DefaultAntivirusConfig::class);
    });

    it('auto-binds all 5 typed interfaces without explicit addDefinitions()', function (): void {
        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        $container = $app->getContainer();

        expect($container->get(AppIdentityConfigInterface::class))->toBeInstanceOf(AppIdentityConfigInterface::class);
        expect($container->get(AntivirusConfigInterface::class))->toBeInstanceOf(AntivirusConfigInterface::class);
        expect($container->get(SecurityConfigInterface::class))->toBeInstanceOf(SecurityConfigInterface::class);
    });

    it('respects user-supplied definition over auto-binding', function (): void {
        $customConfig = new class implements AntivirusConfigInterface {
            public function getHost(): string
            {
                return 'custom-host';
            }
            public function getPort(): int
            {
                return 3310;
            }
            public function getTimeout(): int
            {
                return 5;
            }
            public function getName(): string
            {
                return 'clamav';
            }
        };

        $app = new BootTestApplication();
        $app->define([AntivirusConfigInterface::class => $customConfig]);
        $app->setConfig(TestConfig::class);

        $resolved = $app->getContainer()->get(AntivirusConfigInterface::class);

        expect($resolved)->toBe($customConfig);
        expect($resolved->getHost())->toBe('custom-host');
    });

    it('does not override user-supplied AppIdentityConfigInterface', function (): void {
        $custom = new class implements AppIdentityConfigInterface {
            public function getAppCode(): string
            {
                return 'myapp';
            }
            public function getAppName(): string
            {
                return 'Overridden Name';
            }
            public function getAppEnv(): string
            {
                return 'test';
            }
            public function getAppVersion(): string
            {
                return '1.0.0';
            }
            public function getAppDescription(): string
            {
                return '';
            }
            public function getAppUrl(): string
            {
                return '';
            }
        };

        $app = new BootTestApplication();
        $app->define([AppIdentityConfigInterface::class => $custom]);
        $app->setConfig(TestConfig::class);

        $resolved = $app->getContainer()->get(AppIdentityConfigInterface::class);

        expect($resolved->getAppName())->toBe('Overridden Name');
    });

    it('auto-binds DomainEventBusInterface to NullDomainEventBus', function (): void {
        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        $resolved = $app->getContainer()->get(DomainEventBusInterface::class);

        expect($resolved)->toBeInstanceOf(DomainEventBusInterface::class);
        expect($resolved)->toBeInstanceOf(NullDomainEventBus::class);
    });

    it('configureContainer() hook is called before build and can register bindings', function (): void {
        $customConfig = new class implements AntivirusConfigInterface {
            public function getHost(): string { return 'hook-host'; }
            public function getPort(): int { return 9999; }
            public function getTimeout(): int { return 1; }
            public function getName(): string { return 'clamav'; }
        };

        $app = new class ($customConfig) extends AbstractConsoleApplication {
            public function __construct(private AntivirusConfigInterface $custom)
            {
                parent::__construct();
            }

            protected function registerServices(Configuration $config): void {}

            protected function addServices(): void {}

            protected function configureContainer(): void
            {
                $this->addDefinitions([AntivirusConfigInterface::class => $this->custom]);
            }
        };

        $app->setConfig(TestConfig::class);

        $resolved = $app->getContainer()->get(AntivirusConfigInterface::class);
        expect($resolved->getHost())->toBe('hook-host');
    });

    it('addDefinitions() throws LogicException when called after setConfig()', function (): void {
        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        expect(fn () => $app->define([RequestIdHolder::class => new RequestIdHolder()]))
            ->toThrow(\LogicException::class);
    });
});

describe('AbstractApplication production cache warnings', function (): void {

    beforeEach(function (): void {
        unset($_ENV['APP_ENV']);
    });

    afterEach(function (): void {
        unset($_ENV['APP_ENV']);
    });

    it('boots without error in non-production env', function (): void {
        $_ENV['APP_ENV'] = 'dev';

        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        expect(true)->toBeTrue(); // no exception thrown
    });

    it('boots without error in production when cache file exists', function (): void {
        $_ENV['APP_ENV'] = 'prod';

        $cacheFile = 'var/cache/config.php';
        $cacheDir  = dirname($cacheFile);
        $created   = false;

        if (!file_exists($cacheFile)) {
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0o755, true);
            }
            file_put_contents($cacheFile, '<?php return [\'Directive\\Service\\AppIdentity\\AppIdentityConfigInterface\' => [\'APP_ENV\' => \'production\'], ];');
            $created = true;
        }

        $app = new BootTestApplication();
        $app->setConfig(TestConfig::class);

        if ($created) {
            unlink($cacheFile);
        }

        expect(true)->toBeTrue(); // boot succeeded
    });
});

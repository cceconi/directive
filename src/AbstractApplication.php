<?php

declare(strict_types=1);

namespace Directive;

use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\EventBus\NullDomainEventBus;
use DI\ContainerBuilder;
use Directive\Http\Middleware\DefaultHttpConfig;
use Directive\Http\Middleware\HttpConfigInterface;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\AppIdentity\DefaultAppIdentityConfig;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Logging\RuntimeLogger;
use Directive\Service\Security\Antivirus\AntivirusConfigInterface;
use Directive\Service\Security\Antivirus\DefaultAntivirusConfig;
use Directive\Service\Security\DefaultSecurityConfig;
use Directive\Service\Security\SecurityConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * Base class for WebApplication and ConsoleApplication.
 *
 * Lifecycle:
 *   1. new WebApplication()         — RuntimeLogger up, ContainerBuilder created.
 *   2. ->setConfig(MyConfig::class) — config validated, service defaults bound, container built.
 *   3. ->run()                      — application starts.
 */
abstract class AbstractApplication implements ApplicationInterface
{
    /** @var ContainerBuilder<\DI\Container> */
    private ContainerBuilder $builder;

    private ?ContainerInterface $container = null;

    /** @var array<string> Interface keys already registered by user via addDefinitions(). */
    private array $userDefinedKeys = [];

    public function __construct()
    {
        // RuntimeLogger is bootstrapped first — it catches any PHP error/exception
        // that occurs before the DI container is ready.
        new RuntimeLogger($this->runtimeLoggerName());

        $this->builder = new ContainerBuilder();
        $this->builder->useAutowiring(true);
    }

    // ------------------------------------------------------------------
    // ApplicationInterface
    // ------------------------------------------------------------------

    final public function setConfig(string $configClass): static
    {
        /** @var AbstractConfiguration $config */
        $config = new $configClass();
        $config->audit();

        // Auto-bind the 5 default service configs (skip any already overridden by user).
        $loggingConfig = $this->autoBindServiceDefaults();

        // Re-init RuntimeLogger now that we know the real log directory.
        new RuntimeLogger($this->runtimeLoggerName(), $loggingConfig->getLogPath());

        // Warn if running in production without a compiled config cache.
        $this->checkProductionCacheConfig();

        $this->builder->addDefinitions([$configClass => $config]);

        $this->registerServices($config);

        $this->container = $this->builder->build();

        $this->addServices();

        return $this;
    }

    final public function getContainer(): ContainerInterface
    {
        if ($this->container === null) {
            throw new \LogicException('Container is not built yet. Call setConfig() first.');
        }

        return $this->container;
    }

    // ------------------------------------------------------------------
    // Hooks for subclasses
    // ------------------------------------------------------------------

    /**
     * Register DI definitions that depend on the configuration.
     * Called before the container is built.
     * Override in subclasses to add mode-specific definitions.
     */
    protected function registerServices(AbstractConfiguration $config): void
    {
        // Populated in subsequent epics (loggers, security, managers…).
    }

    /**
     * Called once after the container is built.
     * Use it to perform any post-build wiring (e.g. register API definitions).
     */
    protected function addServices(): void
    {
        // Populated in subsequent epics.
    }

    /**
     * Name used by the RuntimeLogger when no config is available yet.
     * Overridden in ConsoleApplication to return 'console'.
     */
    protected function runtimeLoggerName(): string
    {
        return 'webapp';
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    /**
     * Convenience accessor, only usable after setConfig().
     *
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    protected function get(string $id): object
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Add DI definitions to the builder.
     * Must be called before setConfig() triggers the build.
     * Tracks which interface keys are user-defined so auto-defaults skip them.
     *
     * @param array<string, mixed> $definitions
     */
    protected function addDefinitions(array $definitions): void
    {
        foreach (array_keys($definitions) as $key) {
            $this->userDefinedKeys[] = $key;
        }

        $this->builder->addDefinitions($definitions);
    }

    /**
     * Instantiates, audits, and registers each default service config
     * unless the user already provided that interface binding.
     *
     * Returns the resolved LoggingConfigInterface for RuntimeLogger re-init.
     */
    private function autoBindServiceDefaults(): LoggingConfigInterface
    {
        /** @var array<class-string, AbstractConfiguration|object> $defaults */
        $defaults = [
            LoggingConfigInterface::class     => new DefaultLoggingConfig(),
            AppIdentityConfigInterface::class => new DefaultAppIdentityConfig(),
            AntivirusConfigInterface::class   => new DefaultAntivirusConfig(),
            SecurityConfigInterface::class    => new DefaultSecurityConfig(),
            HttpConfigInterface::class        => new DefaultHttpConfig(),
        ];

        foreach ($defaults as $interface => $impl) {
            if ($impl instanceof AbstractConfiguration) {
                $impl->audit();
            }
            if (!in_array($interface, $this->userDefinedKeys, true)) {
                $this->builder->addDefinitions([$interface => $impl]);
            }
        }

        // Application layer default: NullDomainEventBus (no-op).
        // Override via addDefinitions([DomainEventBusInterface::class => ...]) before setConfig().
        if (!in_array(DomainEventBusInterface::class, $this->userDefinedKeys, true)) {
            $this->builder->addDefinitions([
                DomainEventBusInterface::class => \DI\autowire(NullDomainEventBus::class),
            ]);
        }

        /** @var LoggingConfigInterface $logging */
        $logging = $defaults[LoggingConfigInterface::class];

        return $logging;
    }

    /**
     * Emit warnings when running in production without a config cache.
     */
    private function checkProductionCacheConfig(): void
    {
        $appEnv = $_ENV['APP_ENV'] ?? '';

        if ($appEnv !== 'production') {
            return;
        }

        if (!file_exists('var/cache/config.php')) {
            error_log('[Directive] Production environment detected but var/cache/config.php is missing.'
                . ' Run "php artisan config:compile" to generate the cache.');
        }

        if (isset($_ENV['DIRECTIVE_CONFIG_CACHE']) && $_ENV['DIRECTIVE_CONFIG_CACHE'] === '0') {
            error_log('[Directive] Security warning: configuration cache is explicitly disabled in'
                . ' production (DIRECTIVE_CONFIG_CACHE=0).');
        }
    }
}

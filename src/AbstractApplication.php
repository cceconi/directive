<?php

declare(strict_types=1);

namespace Directive;

use DI\ContainerBuilder;
use Directive\Application\EventBus\DirectiveDomainEventBus;
use Directive\Application\EventBus\DomainEventBusInterface;
use Directive\Application\EventBus\NullDomainEventBus;
use Directive\Http\Middleware\DefaultHttpConfig;
use Directive\Http\Middleware\HttpConfigInterface;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\AppIdentity\DefaultAppIdentityConfig;
use Directive\Service\AppManagement\AppInfo;
use Directive\Service\AppManagement\AppInfoInterface;
use Directive\Service\Configuration\Configuration;
use Directive\Service\Configuration\ConfigProviderInterface;
use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\LoggingConfigInterface;
use Directive\Service\Logging\RuntimeLogger;
use Directive\Service\Maintenance\DefaultMaintenanceConfig;
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

    /** Set in buildServiceDefaults(), available to subclasses during registerServices(). */
    protected AppIdentityConfigInterface $appIdentityConfig;

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

    final public function setConfig(string $configProviderClass): static
    {
        $config = new Configuration();

        // BASE_PATH is injected as a runtime value — it is computed from the
        // entry-point location, not read from $_ENV.
        $config->setRuntimeValue('BASE_PATH', $this->resolveBasePath());

        // Inject compiled cache values before audit() so non-sensitive resolved
        // variables skip $_ENV lookup (sensitive vars are never in the cache).
        $cached = $this->loadConfigCache($configProviderClass);
        if ($cached !== null) {
            $config->loadCache($cached);
        }

        // Service configs declare their keys first.
        $loggingConfig = $this->buildServiceDefaults($config);

        // AppConfig declares last — its declarations win on any key conflict.
        /** @var ConfigProviderInterface $appProvider */
        $appProvider = new $configProviderClass();
        $appProvider->define($config);

        // Resolve all values from $_ENV now that every provider has declared.
        $config->audit();

        // Re-init RuntimeLogger now that we know the real log directory.
        new RuntimeLogger($this->runtimeLoggerName(), $loggingConfig->getLogPath());

        // Warn if running in production without a compiled config cache.
        $this->checkProductionCacheConfig($config);

        $this->builder->addDefinitions([
            Configuration::class => $config,
        ]);

        $this->registerServices($config);

        $this->configureContainer();

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
     * Override to register additional DI bindings before the container is built.
     *
     * Called in setConfig() after registerServices() and before the container
     * is finalized. Use addDefinitions() here to bind application-specific
     * interfaces to their implementations.
     *
     * Example:
     *   $this->addDefinitions([MyInterface::class => new MyImpl()]);
     */
    protected function configureContainer(): void {}

    /**
     * Register DI definitions that depend on the configuration.
     * Called before the container is built.
     * Override in subclasses to add mode-specific definitions.
     */
    protected function registerServices(Configuration $config): void
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
        if ($this->container !== null) {
            throw new \LogicException('Cannot add definitions after the container has been built. Call addDefinitions() before setConfig().');
        }

        foreach (array_keys($definitions) as $key) {
            $this->userDefinedKeys[] = $key;
        }

        $this->builder->addDefinitions($definitions);
    }

    /**
     * Instantiates, calls define(), and registers each default service config
     * unless the user already provided that interface binding.
     *
     * Returns the resolved LoggingConfigInterface for RuntimeLogger re-init.
     */
    private function buildServiceDefaults(Configuration $config): LoggingConfigInterface
    {
        $appInfo = new AppInfo($this->loadAppInfo());

        $this->appIdentityConfig = new DefaultAppIdentityConfig($appInfo, $config);

        /** @var array<class-string, object> $defaults */
        $defaults = [
            AppInfoInterface::class           => $appInfo,
            AppIdentityConfigInterface::class => $this->appIdentityConfig,
            LoggingConfigInterface::class     => new DefaultLoggingConfig($this->appIdentityConfig, $config),
            AntivirusConfigInterface::class   => new DefaultAntivirusConfig($config),
            SecurityConfigInterface::class    => new DefaultSecurityConfig($config),
            HttpConfigInterface::class        => new DefaultHttpConfig($config),
            DefaultMaintenanceConfig::class   => new DefaultMaintenanceConfig($config),
        ];

        foreach ($defaults as $interface => $impl) {
            if ($impl instanceof ConfigProviderInterface) {
                $impl->define($config);
            }
            if (!in_array($interface, $this->userDefinedKeys, true)) {
                $this->builder->addDefinitions([$interface => $impl]);
            }
        }

        // Application layer default: DomainEventBusInterface.
        // If PSR-14 EventDispatcherInterface is resolvable, prefer DirectiveDomainEventBus.
        // Otherwise fall back to NullDomainEventBus (no-op).
        // Never overwrite an explicit user binding.
        if (!in_array(DomainEventBusInterface::class, $this->userDefinedKeys, true)) {
            $psr14Resolvable = false;
            try {
                // Probe without building — check if user already registered a PSR-14 dispatcher.
                $psr14Resolvable = in_array(\Psr\EventDispatcher\EventDispatcherInterface::class, $this->userDefinedKeys, true);
            } catch (\Throwable) {
                $psr14Resolvable = false;
            }

            if ($psr14Resolvable) {
                $this->builder->addDefinitions([
                    DomainEventBusInterface::class => \DI\autowire(DirectiveDomainEventBus::class),
                ]);
            } else {
                $this->builder->addDefinitions([
                    DomainEventBusInterface::class => \DI\autowire(NullDomainEventBus::class),
                ]);
            }
        }

        /** @var LoggingConfigInterface $logging */
        $logging = $defaults[LoggingConfigInterface::class];

        return $logging;
    }

    /**
     * Emit warnings when running in production without a config cache.
     */
    private function checkProductionCacheConfig(Configuration $config): void
    {
        $appEnv = $config->get('APP_ENV');

        if ($appEnv !== $config->get('APP_ENV_PROD_NAME')) {
            return;
        }

        if (!file_exists('var/cache/config.php')) {
            error_log('[Directive] Production environment detected but var/cache/config.php is missing.'
                . ' Run "bin/directive config:compile" to generate the cache.');
        }
    }

    /**
     * Load the compiled config cache for a given config class.
     *
     * Returns the pre-resolved (non-sensitive) values array, or null if the
     * cache is absent, disabled, or has no entry for the given class.
     *
     * @return array<string, mixed>|null
     */
    private function loadConfigCache(string $configProviderClass): ?array
    {
        $cacheFile = 'var/cache/config.php';

        if (!file_exists($cacheFile)) {
            return null;
        }

        /** @var array<string, array<string, mixed>> $cache */
        $cache = include $cacheFile;

        return $cache[$configProviderClass] ?? null;
    }

    /** @return array<string, mixed> */
    private function loadAppInfo(): array
    {
        $file = $this->resolveBasePath() . '/var/appinfo.json';
        if (!is_file($file)) {
            return [];
        }

        $raw = file_get_contents($file);
        if ($raw === false) {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Derive the project root from the web entry-point script location.
     *
     * When served via a web entry point (public/index.php), PHP's CWD is
     * typically the document root (public/), not the project root.
     * We derive the project root from SCRIPT_FILENAME: "public/index.php"
     * lives one level below the project root, so dirname(dirname(...)) gives
     * the right anchor. Falls back to CWD for CLI contexts where the working
     * directory is already the project root.
     */
    private function resolveBasePath(): string
    {
        $scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
        if ($scriptFile !== '') {
            $scriptDir   = dirname(realpath($scriptFile) ?: $scriptFile);
            $projectRoot = dirname($scriptDir);
            if (is_dir($projectRoot . '/var')) {
                return $projectRoot;
            }
        }

        return (string) getcwd();
    }
}

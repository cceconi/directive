<?php

declare(strict_types=1);

namespace Directive;

use DI\ContainerBuilder;
use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Logging\RuntimeLogger;
use Psr\Container\ContainerInterface;

/**
 * Base class for WebApplication and ConsoleApplication.
 *
 * Lifecycle:
 *   1. new WebApplication()         — RuntimeLogger up, ContainerBuilder created.
 *   2. ->setConfig(MyConfig::class) — config validated, container built, services wired.
 *   3. ->run()                      — application starts.
 */
abstract class AbstractApplication implements ApplicationInterface
{
    /** @var ContainerBuilder<\DI\Container> */
    private ContainerBuilder $builder;

    private ?ContainerInterface $container = null;

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
        /** @var ConfigurationInterface $config */
        $config = new $configClass();
        $config->validate();

        // Re-create RuntimeLogger now that we know the real log directory.
        new RuntimeLogger($config->getRuntimeLoggerName(), $config->getLogDir());

        $this->builder->addDefinitions([
            ConfigurationInterface::class => $config,
        ]);

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
    protected function registerServices(ConfigurationInterface $config): void
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
     *
     * @param array<string, mixed> $definitions
     */
    protected function addDefinitions(array $definitions): void
    {
        $this->builder->addDefinitions($definitions);
    }
}

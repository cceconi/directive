<?php

declare(strict_types=1);

namespace Directive;

use Psr\Container\ContainerInterface;

interface ApplicationInterface
{
    /**
     * Boot the configuration and build the DI container.
     * Must be called before run().
     *
     * @param class-string $configClass Fully-qualified name of the user's Configuration class.
     */
    public function setConfig(string $configClass): static;

    /**
     * Start the application (HTTP server or CLI console).
     */
    public function run(): void;

    /**
     * Return the built PSR-11 container.
     * Only available after setConfig() has been called.
     */
    public function getContainer(): ContainerInterface;
}

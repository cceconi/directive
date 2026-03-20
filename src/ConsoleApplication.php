<?php

declare(strict_types=1);

namespace Directive;

use Directive\Console\DirectiveCommand;
use Directive\Console\OpenApiCommand;
use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Logging\ConsoleLogger;
use Directive\Service\Logging\ConsoleLoggerInterface;
use Directive\Service\Logging\WebLoggerInterface;
use Symfony\Component\Console\Application;

/**
 * CLI entry point.
 *
 * Usage:
 *   $app = (new ConsoleApplication())
 *       ->setConfig(MyConfig::class)
 *       ->addCommands([new MyCommand()]);
 *
 *   $app->run();
 */
class ConsoleApplication extends AbstractApplication
{
    private Application $console;

    /** @var DirectiveCommand[] */
    private array $userCommands = [];

    // ------------------------------------------------------------------
    // ApplicationInterface
    // ------------------------------------------------------------------

    public function run(): void
    {
        /** @var ConsoleLoggerInterface $logger */
        $logger = $this->get(ConsoleLoggerInterface::class);
        $logger->logVersion($this->console->getVersion());

        $this->console->run();

        $logger->write();
    }

    // ------------------------------------------------------------------
    // Public API
    // ------------------------------------------------------------------

    /**
     * Register additional commands.
     * Can be called before or after setConfig().
     *
     * @param DirectiveCommand[] $commands
     */
    public function addCommands(array $commands): static
    {
        $this->userCommands = array_merge($this->userCommands, $commands);

        if (isset($this->console)) {
            $this->console->addCommands($commands);
        }

        return $this;
    }

    // ------------------------------------------------------------------
    // Hooks
    // ------------------------------------------------------------------

    protected function runtimeLoggerName(): string
    {
        return 'console';
    }

    protected function registerServices(ConfigurationInterface $config): void
    {
        parent::registerServices($config);

        // Build ConsoleLogger and register it for both its own interface
        // and WebLoggerInterface so middlewares relying on WebLoggerInterface work.
        $logger = new ConsoleLogger(
            channel:  (string) $config->get('app.code', 'apisy-console'),
            logDir:   $config->getLogDir(),
            config:   $config,
        );

        $this->addDefinitions([
            ConsoleLoggerInterface::class => $logger,
            WebLoggerInterface::class     => $logger,
        ]);
    }

    protected function addServices(): void
    {
        parent::addServices();
        $this->bootConsole();
    }

    // ------------------------------------------------------------------
    // Console bootstrap
    // ------------------------------------------------------------------

    private function bootConsole(): void
    {
        /** @var ConfigurationInterface $config */
        $config = $this->get(ConfigurationInterface::class);

        $this->console = new Application(
            name:    (string) $config->get('app.name', 'Directive'),
            version: '3.0',
        );

        // Always-present framework commands
        $this->console->add(new OpenApiCommand($this->getContainer()));

        // User-registered commands
        if ($this->userCommands !== []) {
            $this->console->addCommands($this->userCommands);
        }
    }
}

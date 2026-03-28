<?php

declare(strict_types=1);

namespace Directive;

use Directive\Console\ConfigAuditCommand;
use Directive\Console\ConfigCheckCommand;
use Directive\Console\ConfigCompileCommand;
use Directive\Console\ConfigExportCommand;
use Directive\Console\ConfigListCommand;
use Directive\Console\ConfigVerifyCommand;
use Directive\Console\DirectiveCommand;
use Directive\Console\FeatureListCommand;
use Directive\Console\OpenApiCommand;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Logging\ConsoleLogger;
use Directive\Service\Logging\ConsoleLoggerInterface;
use Directive\Service\Logging\DefaultLoggingConfig;
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

    protected function registerServices(AbstractConfiguration $config): void
    {
        parent::registerServices($config);

        // Build ConsoleLogger and register it for both its own interface
        // and WebLoggerInterface so middlewares relying on WebLoggerInterface work.
        $loggingConfig = new DefaultLoggingConfig();
        $loggingConfig->audit();

        $logger = new ConsoleLogger(
            channel: $loggingConfig->getAppCode(),
            logDir: $loggingConfig->getLogPath(),
            config: $loggingConfig,
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
        /** @var AppIdentityConfigInterface $appId */
        $appId = $this->get(AppIdentityConfigInterface::class);

        $this->console = new Application(
            name: $appId->getAppName(),
            version: '3.0',
        );

        // Always-present framework commands
        $this->console->addCommand(new OpenApiCommand($this->getContainer()));
        $this->console->addCommand(new ConfigCheckCommand($this->getContainer()));
        $this->console->addCommand(new ConfigListCommand($this->getContainer()));
        $this->console->addCommand(new ConfigExportCommand($this->getContainer()));
        $this->console->addCommand(new ConfigAuditCommand($this->getContainer()));
        $this->console->addCommand(new ConfigCompileCommand($this->getContainer()));
        $this->console->addCommand(new FeatureListCommand($this->getContainer()));
        $this->console->addCommand(new ConfigVerifyCommand($this->getContainer()));

        // User-registered commands
        if ($this->userCommands !== []) {
            $this->console->addCommands($this->userCommands);
        }
    }
}

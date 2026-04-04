<?php

declare(strict_types=1);

namespace Directive;

use Directive\Cli\DirectiveCommand;
use Directive\Console\ConfigAuditCommand;
use Directive\Console\ConfigCheckCommand;
use Directive\Console\ConfigCompileCommand;
use Directive\Console\ConfigExportCommand;
use Directive\Console\ConfigListCommand;
use Directive\Console\ConfigVerifyCommand;
use Directive\Console\FeatureListCommand;
use Directive\Console\Generator\ConfigureDefaultCommand;
use Directive\Console\Generator\GenerateEventCommand;
use Directive\Console\Generator\GenerateExceptionCommand;
use Directive\Console\Generator\GenerateRepositoryInterfaceCommand;
use Directive\Console\Generator\GenerateRoleCommand;
use Directive\Console\Generator\GenerateUidCommand;
use Directive\Console\Generator\GenerateUseCaseCommandCommand;
use Directive\Console\Generator\GenerateUseCaseQueryCommand;
use Directive\Console\OpenApiCommand;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Logging\DefaultLoggingConfig;
use Directive\Service\Logging\DirectiveLogger;
use Directive\Service\Logging\RequestIdHolder;
use Psr\Log\LoggerInterface;
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
        /** @var LoggerInterface $logger */
        $logger = $this->get(LoggerInterface::class);
        $logger->info('console.start', ['version' => $this->console->getVersion()]);

        $this->console->run();
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

        $holder = new RequestIdHolder();
        $logger = new DirectiveLogger($loggingConfig, $holder);

        $this->addDefinitions([
            LoggerInterface::class  => $logger,
            DirectiveLogger::class  => $logger,
            RequestIdHolder::class  => $holder,
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
        $this->console->addCommand(new ConfigureDefaultCommand($this->getContainer()));
        $this->console->addCommand(new GenerateUseCaseCommandCommand($this->getContainer()));
        $this->console->addCommand(new GenerateUseCaseQueryCommand($this->getContainer()));
        $this->console->addCommand(new GenerateRoleCommand($this->getContainer()));
        $this->console->addCommand(new GenerateEventCommand($this->getContainer()));
        $this->console->addCommand(new GenerateRepositoryInterfaceCommand($this->getContainer()));
        $this->console->addCommand(new GenerateExceptionCommand($this->getContainer()));
        $this->console->addCommand(new GenerateUidCommand($this->getContainer()));

        // User-registered commands
        if ($this->userCommands !== []) {
            $this->console->addCommands($this->userCommands);
        }
    }
}

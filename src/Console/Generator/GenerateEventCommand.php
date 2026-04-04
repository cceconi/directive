<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

use Directive\Cli\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'generate:event',
    description: 'Scaffold a domain event pair (DomainEvent + EventHandler).',
)]
final class GenerateEventCommand extends DirectiveCommand
{
    private readonly CodeGeneratorService $generator;
    private readonly GeneratorConfigResolver $config;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        parent::__construct($container);
        $this->generator = new CodeGeneratorService();
        $this->config    = new GeneratorConfigResolver();
    }

    protected function configure(): void
    {
        $this->addArgument('EventName', InputArgument::OPTIONAL, 'Event name without Domain Event suffix (e.g. OrderPlaced)');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $resolved = $this->config->resolve();
        if ($resolved !== null) {
            if ($this->config->hasMultiplePsr4Mappings()) {
                $output->writeln('<comment>Note: Multiple PSR-4 autoload mappings in composer.json — using the first one. Run <info>generator:configure-default</info> to pin your settings.</comment>');
            }

            [$namespace, $src] = [$resolved['namespace'], $resolved['src']];
        } else {
            $namespace = (string) $helper->ask($input, $output, new Question('<info>Root namespace</info>: '));
            $src       = (string) $helper->ask($input, $output, new Question('<info>Source directory</info>: '));

            if ($namespace === '' || $src === '') {
                $output->writeln('<error>Namespace and source directory are required. Run generator:configure-default first.</error>');

                return self::FAILURE;
            }
        }

        $name = $input->getArgument('EventName');
        if (!is_string($name) || $name === '') {
            $name = (string) $helper->ask($input, $output, new Question('<info>Event name (e.g. OrderPlaced)</info>: '));
        }

        if ($name === '') {
            $output->writeln('<error>EventName is required.</error>');

            return self::FAILURE;
        }

        $targetDir = $src . '/Event';

        $this->generator->generate('', $name, $targetDir, $namespace, ClassType::EVENT->value);
        $this->generator->generate('', $name, $targetDir, $namespace, ClassType::EVENT_HANDLER->value);

        $output->writeln(sprintf(
            '<info>✓</info> Scaffolded <comment>%sDomainEvent</comment> + <comment>%sEventHandler</comment> in <comment>%s</comment>',
            $name,
            $name,
            $targetDir,
        ));

        return self::SUCCESS;
    }
}

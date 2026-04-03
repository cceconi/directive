<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

use Directive\Console\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'generate:usecase-command',
    description: 'Scaffold a UseCase with a Command input (UseCase, Command, Payload, Result, UseCaseInterface).',
)]
final class GenerateUseCaseCommandCommand extends DirectiveCommand
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
        $this
            ->addArgument('Business', InputArgument::OPTIONAL, 'Business context name (e.g. Order)')
            ->addArgument('UseCaseName', InputArgument::OPTIONAL, 'Use case name (e.g. CreateOrder)');
    }

    protected function executeCommand(InputInterface $input, OutputInterface $output): int
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        [$namespace, $src] = $this->resolveConfig($input, $output, $helper);
        if ($namespace === null || $src === null) {
            return self::FAILURE;
        }

        $business = $this->resolveArg($input, $output, $helper, 'Business', 'Business context (e.g. Order)');
        $name     = $this->resolveArg($input, $output, $helper, 'UseCaseName', 'Use case name (e.g. CreateOrder)');

        if ($business === '' || $name === '') {
            $output->writeln('<error>Business and UseCaseName are required.</error>');

            return self::FAILURE;
        }

        $useCaseDir    = $src . '/UseCase/' . $business;
        $interfaceDir  = $src . '/Api/' . $business;

        $this->generator->generate($business, $name, $useCaseDir,   $namespace, ClassType::USE_CASE->value, ['input' => 'Command']);
        $this->generator->generate($business, $name, $useCaseDir,   $namespace, ClassType::COMMAND->value);
        $this->generator->generate($business, $name, $useCaseDir,   $namespace, ClassType::PAYLOAD->value);
        $this->generator->generate($business, $name, $useCaseDir,   $namespace, ClassType::RESULT->value);
        $this->generator->generate($business, $name, $interfaceDir, $namespace, ClassType::USE_CASE_INTERFACE->value);

        $output->writeln(sprintf('<info>✓</info> Scaffolded <comment>%s</comment> UseCase (command) in <comment>%s</comment>', $name, $useCaseDir));

        return self::SUCCESS;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveConfig(InputInterface $input, OutputInterface $output, QuestionHelper $helper): array
    {
        $resolved = $this->config->resolve();

        if ($resolved !== null) {
            if ($this->config->hasMultiplePsr4Mappings()) {
                $output->writeln('<comment>Note: Multiple PSR-4 autoload mappings in composer.json — using the first one. Run <info>generator:configure-default</info> to pin your settings.</comment>');
            }

            return [$resolved['namespace'], $resolved['src']];
        }

        $namespace = (string) $helper->ask($input, $output, new Question('<info>Root namespace</info>: '));
        $src       = (string) $helper->ask($input, $output, new Question('<info>Source directory</info>: '));

        if ($namespace === '' || $src === '') {
            $output->writeln('<error>Namespace and source directory are required. Run generator:configure-default first.</error>');

            return [null, null];
        }

        return [$namespace, $src];
    }

    private function resolveArg(
        InputInterface $input,
        OutputInterface $output,
        QuestionHelper $helper,
        string $argName,
        string $label,
    ): string {
        $value = $input->getArgument($argName);
        if (is_string($value) && $value !== '') {
            return $value;
        }

        return (string) $helper->ask($input, $output, new Question("<info>{$label}</info>: "));
    }
}

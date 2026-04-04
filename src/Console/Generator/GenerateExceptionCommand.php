<?php

declare(strict_types=1);

namespace Directive\Console\Generator;

use Directive\Cli\DirectiveCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'generate:exception',
    description: 'Scaffold a domain exception extending the appropriate AbstractDomainException sub-class.',
)]
final class GenerateExceptionCommand extends DirectiveCommand
{
    /** @var array<string, string> */
    private const TYPE_MAP = [
        'entity-not-found' => 'EntityNotFoundException',
        'access-denied'    => 'AccessDeniedException',
        'conflict'         => 'ConflictException',
        'validation'       => 'ValidationException',
        'business-rule'    => 'BusinessRuleException',
    ];

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
            ->addArgument('ExceptionName', InputArgument::OPTIONAL, 'Exception name without Exception suffix (e.g. OrderNotFound)')
            ->addArgument('type', InputArgument::OPTIONAL, sprintf(
                'Exception type: %s',
                implode(', ', array_keys(self::TYPE_MAP)),
            ));
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

        $name = $input->getArgument('ExceptionName');
        if (!is_string($name) || $name === '') {
            $name = (string) $helper->ask($input, $output, new Question('<info>Exception name (e.g. OrderNotFound)</info>: '));
        }

        if ($name === '') {
            $output->writeln('<error>ExceptionName is required.</error>');

            return self::FAILURE;
        }

        $type = $input->getArgument('type');

        if (!is_string($type) || !array_key_exists($type, self::TYPE_MAP)) {
            $choiceQuestion = new ChoiceQuestion(
                '<info>Exception type</info>:',
                array_keys(self::TYPE_MAP),
            );
            $type = (string) $helper->ask($input, $output, $choiceQuestion);
        }

        $parent    = self::TYPE_MAP[$type];
        $targetDir = $src . '/Exception';

        $this->generator->generate('', $name, $targetDir, $namespace, ClassType::EXCEPTION->value, ['parent' => $parent]);

        $output->writeln(sprintf(
            '<info>✓</info> Scaffolded <comment>%sException</comment> extends <comment>%s</comment> in <comment>%s</comment>',
            $name,
            $parent,
            $targetDir,
        ));

        return self::SUCCESS;
    }
}

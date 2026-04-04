<?php

declare(strict_types=1);

use Directive\Console\FeatureListCommand;
use Directive\Service\Configuration\AbstractFeatures;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

function makeFeatures(): AbstractFeatures
{
    return new class extends AbstractFeatures {
        protected function define(): void
        {
            $this->feature('dark_mode', 'FEATURE_DARK_MODE', false);
        }
    };
}

function makeFeatureContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return makeFeatures();
        }
        public function has(string $id): bool
        {
            return $id === AbstractFeatures::class;
        }
    };
}

describe('FeatureListCommand', function (): void {

    it('renders a table with declared feature flags', function (): void {
        unset($_ENV['FEATURE_DARK_MODE']);
        $command = new FeatureListCommand(makeFeatureContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
        expect($tester->getDisplay())->toContain('dark_mode');
    });

    it('succeeds gracefully when no AbstractFeatures is bound', function (): void {
        $container = new class implements ContainerInterface {
            public function get(string $id): mixed
            {
                return null;
            }
            public function has(string $id): bool
            {
                return false;
            }
        };
        $command = new FeatureListCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute([]);
        expect($tester->getStatusCode())->toBe(0);
    });
});

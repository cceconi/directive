<?php

declare(strict_types=1);

use Directive\Console\Generator\ConfigureDefaultCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeNullContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            return null;
        }
        public function has(string $id): bool
        {
            return false;
        }
    };
}

function makeConfigureTester(): CommandTester
{
    $command = new ConfigureDefaultCommand(makeNullContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generator:configure-default'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-cfgcmd-' . uniqid();
    mkdir($this->cwd);
    chdir($this->cwd);
});

afterEach(function (): void {
    array_map('unlink', glob($this->cwd . '/*') ?: []);
    rmdir($this->cwd);
});

describe('ConfigureDefaultCommand', function (): void {

    it('writes directive-dev.json from interactive input', function (): void {
        $tester = makeConfigureTester();
        $tester->setInputs(['MyApp', 'src']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        $json = json_decode((string) file_get_contents($this->cwd . '/directive-dev.json'), true);
        expect($json)->toBe(['namespace' => 'MyApp', 'src' => 'src']);
    });

    it('suggests PSR-4 values from composer.json', function (): void {
        file_put_contents(
            $this->cwd . '/composer.json',
            json_encode(['autoload' => ['psr-4' => ['App\\' => 'src/']]]),
        );

        $tester = makeConfigureTester();
        // Accept suggestions (empty input → uses default)
        $tester->setInputs(['', '']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        $json = json_decode((string) file_get_contents($this->cwd . '/directive-dev.json'), true);
        expect($json['namespace'])->toBe('App');
        expect($json['src'])->toBe('src');
    });

    it('fails when namespace is empty and no default', function (): void {
        $tester = makeConfigureTester();
        $tester->setInputs(['', '']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(1);
    });

    it('pre-fills suggestions from existing directive-dev.json on reconfiguration', function (): void {
        file_put_contents(
            $this->cwd . '/directive-dev.json',
            json_encode(['namespace' => 'Existing\\App', 'src' => 'existing-src']),
        );

        $tester = makeConfigureTester();
        // Accept existing suggestions (empty input → uses default)
        $tester->setInputs(['', '']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        $json = json_decode((string) file_get_contents($this->cwd . '/directive-dev.json'), true);
        expect($json['namespace'])->toBe('Existing\\App');
        expect($json['src'])->toBe('existing-src');
    });

});

<?php

declare(strict_types=1);

use Directive\Console\Generator\GenerateRoleCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

function makeRoleContainer(): ContainerInterface
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

function makeRoleTester(): CommandTester
{
    $command = new GenerateRoleCommand(makeRoleContainer());
    $app     = new Application();
    $app->addCommand($command);
    return new CommandTester($app->find('generate:role'));
}

beforeEach(function (): void {
    $this->cwd = sys_get_temp_dir() . '/directive-role-' . uniqid();
    mkdir($this->cwd);
    file_put_contents(
        $this->cwd . '/directive-dev.json',
        json_encode(['namespace' => 'App', 'src' => $this->cwd . '/src']),
    );
    chdir($this->cwd);
});

afterEach(function (): void {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->cwd, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $file) {
        /** @var SplFileInfo $file */
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }
    rmdir($this->cwd);
});

describe('GenerateRoleCommand', function (): void {

    it('generates a Role file in Shared/Role/', function (): void {
        $tester = makeRoleTester();
        $tester->execute(['RoleName' => 'Admin']);

        $file = $this->cwd . '/src/Shared/Role/AdminRole.php';
        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($file))->toBeTrue();
    });

    it('slug is strtolower of the name', function (): void {
        $tester = makeRoleTester();
        $tester->execute(['RoleName' => 'SuperAdmin']);

        $content = (string) file_get_contents($this->cwd . '/src/Shared/Role/SuperAdminRole.php');
        expect($content)->toContain("return 'superadmin'");
    });

    it('accepts name via interactive mode', function (): void {
        $tester = makeRoleTester();
        $tester->setInputs(['Guest']);
        $tester->execute([]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($this->cwd . '/src/Shared/Role/GuestRole.php'))->toBeTrue();
    });

});

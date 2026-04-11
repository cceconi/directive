<?php

declare(strict_types=1);

use Directive\Console\CiAppInfoCommand;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeCiContainer(): ContainerInterface
{
    return new class implements ContainerInterface {
        public function get(string $id): mixed { return null; }
        public function has(string $id): bool  { return false; }
    };
}

/**
 * Run ci:appinfo inside a dedicated temp dir so `var/appinfo.json` is isolated.
 *
 * @param array<string, string> $options  CLI options to pass (e.g. ['--name' => 'app'])
 * @return array{code: int, display: string, file: string}
 */
function runCiAppInfo(string $tmpRoot, array $options = []): array
{
    $originalCwd = getcwd();
    chdir($tmpRoot);

    $command = new CiAppInfoCommand(makeCiContainer());
    $tester  = new CommandTester($command);
    $tester->execute($options);

    $code    = $tester->getStatusCode();
    $display = $tester->getDisplay();

    chdir((string) $originalCwd);

    return [
        'code'    => $code,
        'display' => $display,
        'file'    => $tmpRoot . '/var/appinfo.json',
    ];
}

function cleanupTmp(string $tmpRoot): void
{
    @unlink($tmpRoot . '/var/appinfo.json');
    @rmdir($tmpRoot . '/var');
    @rmdir($tmpRoot);
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('CiAppInfoCommand', function (): void {

    it('generates var/appinfo.json with explicit options', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        $result = runCiAppInfo($tmpRoot, [
            '--name'         => 'my-app',
            '--app-version'  => '1.2.3',
            '--commit'       => 'abc1234',
            '--branch'       => 'main',
            '--tag'          => 'v1.2.3',
            '--build-number' => '42',
            '--built-by'     => 'github-actions',
        ]);

        expect($result['code'])->toBe(0);
        expect(is_file($result['file']))->toBeTrue();

        /** @var array<string, string> $data */
        $data = json_decode((string) file_get_contents($result['file']), true);

        expect($data['name'])->toBe('my-app');
        expect($data['version'])->toBe('1.2.3');
        expect($data['commitId'])->toBe('abc1234');
        expect($data['branch'])->toBe('main');
        expect($data['tag'])->toBe('v1.2.3');
        expect($data['buildNumber'])->toBe('42');
        expect($data['builtBy'])->toBe('github-actions');
        expect($data)->toHaveKey('builtAt');

        cleanupTmp($tmpRoot);
    });

    it('shows absolute path in success message', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-abs-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        $result = runCiAppInfo($tmpRoot, ['--name' => 'my-app', '--app-version' => '0.1.0']);

        expect($result['display'])->toContain($tmpRoot);

        cleanupTmp($tmpRoot);
    });

    it('falls back to env vars when options are not provided', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-env-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        putenv('GITHUB_SHA=deadbeef');
        putenv('GITHUB_REF_NAME=feature/x');
        putenv('GITHUB_RUN_NUMBER=99');
        putenv('GITHUB_ACTOR=octocat');

        $result = runCiAppInfo($tmpRoot, ['--name' => 'env-app', '--app-version' => '2.0.0']);

        /** @var array<string, string> $data */
        $data = json_decode((string) file_get_contents($result['file']), true);

        expect($data['commitId'])->toBe('deadbeef');
        expect($data['branch'])->toBe('feature/x');
        expect($data['buildNumber'])->toBe('99');
        expect($data['builtBy'])->toBe('octocat');

        putenv('GITHUB_SHA');
        putenv('GITHUB_REF_NAME');
        putenv('GITHUB_RUN_NUMBER');
        putenv('GITHUB_ACTOR');

        cleanupTmp($tmpRoot);
    });

    it('explicit option takes precedence over env var', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-prio-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        putenv('GITHUB_SHA=env-sha');

        $result = runCiAppInfo($tmpRoot, [
            '--name'       => 'prio-app',
            '--app-version' => '1.0.0',
            '--commit'     => 'explicit-sha',
        ]);

        /** @var array<string, string> $data */
        $data = json_decode((string) file_get_contents($result['file']), true);

        expect($data['commitId'])->toBe('explicit-sha');

        putenv('GITHUB_SHA');
        cleanupTmp($tmpRoot);
    });

    it('writes to a custom --output path', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-out-' . uniqid();
        mkdir($tmpRoot . '/custom', 0o755, true);

        $customFile = $tmpRoot . '/custom/build.json';

        $command = new CiAppInfoCommand(makeCiContainer());
        $tester  = new CommandTester($command);

        $originalCwd = getcwd();
        chdir($tmpRoot);
        $tester->execute(['--name' => 'out-app', '--app-version' => '1.0.0', '--output' => 'custom/build.json']);
        chdir((string) $originalCwd);

        expect($tester->getStatusCode())->toBe(0);
        expect(is_file($customFile))->toBeTrue();

        @unlink($customFile);
        @rmdir($tmpRoot . '/custom');
        @rmdir($tmpRoot);
    });

    it('fails with INVALID when --name is missing', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-noname-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        $result = runCiAppInfo($tmpRoot, ['--app-version' => '1.0.0']);

        expect($result['code'])->toBe(2); // Command::INVALID
        expect(is_file($result['file']))->toBeFalse();

        @rmdir($tmpRoot);
    });

    it('fails with INVALID when --app-version is missing', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-ci-appinfo-nover-' . uniqid();
        mkdir($tmpRoot, 0o755, true);

        $result = runCiAppInfo($tmpRoot, ['--name' => 'my-app']);

        expect($result['code'])->toBe(2); // Command::INVALID
        expect(is_file($result['file']))->toBeFalse();

        @rmdir($tmpRoot);
    });
});

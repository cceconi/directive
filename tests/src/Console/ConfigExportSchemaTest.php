<?php

declare(strict_types=1);

use Directive\Console\ConfigExportCommand;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Configuration\AbstractConfiguration;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeSchemaConfig(): AbstractConfiguration
{
    return new class extends AbstractConfiguration {
        protected function define(): void
        {
            $this->required('APP_ENV', 'string');
            $this->optional('LOG_LEVEL', 'info', 'string', ['debug', 'info', 'warning', 'error']);
            $this->optional('CACHE_TTL', 300, 'int');
        }
    };
}

function makeSchemaContainer(bool $hasIdentity = false, string $version = '1.2.3'): ContainerInterface
{
    return new class ($hasIdentity, $version) implements ContainerInterface {
        public function __construct(private bool $hasId, private string $ver) {}

        public function get(string $id): mixed
        {
            if ($id === AbstractConfiguration::class) {
                return makeSchemaConfig();
            }

            if ($id === AppIdentityConfigInterface::class) {
                $ver = $this->ver;
                return new class ($ver) implements AppIdentityConfigInterface {
                    public function __construct(private string $v) {}

                    public function getAppCode(): string
                    {
                        return 'test';
                    }

                    public function getAppName(): string
                    {
                        return 'test-app';
                    }

                    public function getAppVersion(): string
                    {
                        return $this->v;
                    }

                    public function getAppDescription(): string
                    {
                        return 'Test app';
                    }

                    public function getAppUrl(): string
                    {
                        return 'https://example.com';
                    }
                };
            }

            return null;
        }

        public function has(string $id): bool
        {
            if ($id === AbstractConfiguration::class) {
                return true;
            }

            return $this->hasId && $id === AppIdentityConfigInterface::class;
        }
    };
}

function runExportSchema(ContainerInterface $container, array $input = []): CommandTester
{
    $tmpDir = sys_get_temp_dir() . '/directive-schema-test-' . uniqid();
    mkdir($tmpDir, 0o755, true);
    $originalCwd = getcwd();
    chdir($tmpDir);

    $command = new ConfigExportCommand($container);
    $tester  = new CommandTester($command);
    $tester->execute(array_merge(['--schema' => true], $input));

    chdir((string) $originalCwd);

    // Attach tmpDir so test can read the file
    $tester->setInputs([]);
    /** @phpstan-ignore-next-line */
    $tester->tmpDir = $tmpDir;

    return $tester;
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('ConfigExportCommand --schema', function (): void {

    it('generates a valid config-schema.json', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-valid-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true]);

        chdir((string) $cwd);

        expect($tester->getStatusCode())->toBe(0);
        expect(is_file($tmpDir . '/config-schema.json'))->toBeTrue();

        $json = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        expect($json)->toBeArray();
        expect($json)->toHaveKey('appVersion');
        expect($json)->toHaveKey('generatedAt');
        expect($json)->toHaveKey('variables');
        expect($json['variables'])->toBeArray();

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });

    it('embeds appVersion from AppIdentityConfigInterface when available', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-version-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer(hasIdentity: true, version: '3.2.1'));
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true]);

        chdir((string) $cwd);

        $json = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        expect($json['appVersion'])->toBe('3.2.1');

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });

    it('overrides appVersion via --version flag', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-override-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer(hasIdentity: true, version: '1.0.0'));
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true, '--version' => '9.9.9']);

        chdir((string) $cwd);

        $json = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        expect($json['appVersion'])->toBe('9.9.9');

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });

    it('falls back to 0.0.0 when no identity config bound', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-fallback-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer(hasIdentity: false));
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true]);

        chdir((string) $cwd);

        $json = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        expect($json['appVersion'])->toBe('0.0.0');

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });

    it('includes allowed values in schema for constrained variables', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-allowed-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true]);

        chdir((string) $cwd);

        $json = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        $logLevel = array_filter($json['variables'], fn($v) => $v['key'] === 'LOG_LEVEL');
        $logLevel = array_values($logLevel)[0] ?? null;

        expect($logLevel)->not->toBeNull();
        expect($logLevel['allowed'])->toContain('debug');
        expect($logLevel['allowed'])->toContain('error');

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });

    it('marks required variables correctly in schema', function (): void {
        $tmpDir = sys_get_temp_dir() . '/directive-schema-required-' . uniqid();
        mkdir($tmpDir, 0o755, true);
        $cwd = getcwd();
        chdir($tmpDir);

        $command = new ConfigExportCommand(makeSchemaContainer());
        $tester  = new CommandTester($command);
        $tester->execute(['--schema' => true]);

        chdir((string) $cwd);

        $json     = json_decode((string) file_get_contents($tmpDir . '/config-schema.json'), true);
        $appEnv   = array_filter($json['variables'], fn($v) => $v['key'] === 'APP_ENV');
        $appEnv   = array_values($appEnv)[0] ?? null;
        $cacheTtl = array_filter($json['variables'], fn($v) => $v['key'] === 'CACHE_TTL');
        $cacheTtl = array_values($cacheTtl)[0] ?? null;

        expect($appEnv['required'])->toBeTrue();
        expect($cacheTtl['required'])->toBeFalse();
        expect($cacheTtl)->toHaveKey('default');
        expect($cacheTtl['default'])->toBe(300);

        @unlink($tmpDir . '/config-schema.json');
        @rmdir($tmpDir);
    });
});

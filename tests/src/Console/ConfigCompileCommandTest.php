<?php

declare(strict_types=1);

use Directive\Console\ConfigCompileCommand;
use Directive\Service\Configuration\AbstractConfiguration;
use Directive\Service\Configuration\ConfigSourceTracker;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

// ── Helpers ───────────────────────────────────────────────────────────────────

function makeCompileConfig(): AbstractConfiguration
{
    return new class extends AbstractConfiguration {
        protected function define(): void
        {
            $this->required('APP_ENV', 'string');
            $this->optional('APP_SECRET', 'secret-default', 'string');
            $this->optional('APP_KEY', 'key-default', 'string');
            $this->optional('CACHE_TTL', 300, 'int');
        }
    };
}

function makeCompileContainer(bool $hasConfig = true): ContainerInterface
{
    return new class ($hasConfig) implements ContainerInterface {
        public function __construct(private bool $has) {}

        public function get(string $id): mixed
        {
            return $this->has ? makeCompileConfig() : null;
        }

        public function has(string $id): bool
        {
            return $this->has && $id === AbstractConfiguration::class;
        }
    };
}

/**
 * Run config:compile with a custom CACHE_FILE location (by patching CWD or symlink).
 * We use a temporary directory as CWD so the command writes to var/cache/config.php there.
 */
function runCompileInTmpDir(ContainerInterface $container, string $tmpRoot): int
{
    $originalCwd = getcwd();

    chdir($tmpRoot);

    mkdir($tmpRoot . '/var/cache', 0755, true);

    $command = new ConfigCompileCommand($container);
    $tester  = new CommandTester($command);
    $tester->execute([]);

    $code = $tester->getStatusCode();

    chdir((string) $originalCwd);

    return $code;
}

// ── Tests ─────────────────────────────────────────────────────────────────────

describe('ConfigCompileCommand', function (): void {

    beforeEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['APP_SECRET'], $_ENV['APP_KEY'], $_ENV['CACHE_TTL']);
    });

    afterEach(function (): void {
        ConfigSourceTracker::reset();
        unset($_ENV['APP_ENV'], $_ENV['APP_SECRET'], $_ENV['APP_KEY'], $_ENV['CACHE_TTL']);
    });

    it('generates var/cache/config.php successfully', function (): void {
        $_ENV['APP_ENV'] = 'production';

        $tmpRoot = sys_get_temp_dir() . '/directive-compile-test-' . uniqid();
        mkdir($tmpRoot, 0755, true);

        $exitCode = runCompileInTmpDir(makeCompileContainer(), $tmpRoot);

        expect($exitCode)->toBe(0);
        expect(is_file($tmpRoot . '/var/cache/config.php'))->toBeTrue();

        // Cache file must be valid PHP returning an array
        $data = require $tmpRoot . '/var/cache/config.php';
        expect($data)->toBeArray();

        // Cleanup
        @unlink($tmpRoot . '/var/cache/config.php');
        @rmdir($tmpRoot . '/var/cache');
        @rmdir($tmpRoot . '/var');
        @rmdir($tmpRoot);
    });

    it('does not write sensitive variables (SECRET, KEY) to the cache', function (): void {
        $_ENV['APP_ENV']    = 'production';
        $_ENV['APP_SECRET'] = 'real-secret-value';
        $_ENV['APP_KEY']    = 'real-key-value';

        $tmpRoot = sys_get_temp_dir() . '/directive-compile-secret-' . uniqid();
        mkdir($tmpRoot, 0755, true);

        runCompileInTmpDir(makeCompileContainer(), $tmpRoot);

        $content = (string) file_get_contents($tmpRoot . '/var/cache/config.php');
        expect($content)->not->toContain('real-secret-value');
        expect($content)->not->toContain('real-key-value');
        expect($content)->not->toContain('APP_SECRET');
        expect($content)->not->toContain('APP_KEY');

        // Cleanup
        @unlink($tmpRoot . '/var/cache/config.php');
        @rmdir($tmpRoot . '/var/cache');
        @rmdir($tmpRoot . '/var');
        @rmdir($tmpRoot);
    });

    it('overwrites an existing cache file without error', function (): void {
        $_ENV['APP_ENV'] = 'production';

        $tmpRoot = sys_get_temp_dir() . '/directive-compile-overwrite-' . uniqid();
        mkdir($tmpRoot . '/var/cache', 0755, true);
        file_put_contents($tmpRoot . '/var/cache/config.php', '<?php return ["old" => true];');

        $originalCwd = getcwd();
        chdir($tmpRoot);

        $command = new ConfigCompileCommand(makeCompileContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        $exitCode = $tester->getStatusCode();
        chdir((string) $originalCwd);

        expect($exitCode)->toBe(0);

        $content = (string) file_get_contents($tmpRoot . '/var/cache/config.php');
        expect($content)->not->toContain('"old"');

        // Cleanup
        @unlink($tmpRoot . '/var/cache/config.php');
        @rmdir($tmpRoot . '/var/cache');
        @rmdir($tmpRoot . '/var');
        @rmdir($tmpRoot);
    });

    it('fails with exit code 1 when var/cache/ directory does not exist', function (): void {
        $_ENV['APP_ENV'] = 'production';

        $tmpRoot = sys_get_temp_dir() . '/directive-compile-nodir-' . uniqid();
        mkdir($tmpRoot, 0755, true);
        // Do NOT create var/cache/

        $originalCwd = getcwd();
        chdir($tmpRoot);

        $command = new ConfigCompileCommand(makeCompileContainer());
        $tester  = new CommandTester($command);
        $tester->execute([]);

        $exitCode = $tester->getStatusCode();
        chdir((string) $originalCwd);

        expect($exitCode)->toBe(1);
        expect($tester->getDisplay())->toContain('does not exist');

        @rmdir($tmpRoot);
    });

    it('exits successfully with a message when no config is bound', function (): void {
        $tmpRoot = sys_get_temp_dir() . '/directive-compile-noconf-' . uniqid();
        mkdir($tmpRoot . '/var/cache', 0755, true);

        $originalCwd = getcwd();
        chdir($tmpRoot);

        $command = new ConfigCompileCommand(makeCompileContainer(false));
        $tester  = new CommandTester($command);
        $tester->execute([]);

        $exitCode = $tester->getStatusCode();
        chdir((string) $originalCwd);

        expect($exitCode)->toBe(0);
        expect($tester->getDisplay())->toContain('No AbstractConfiguration');

        @rmdir($tmpRoot . '/var/cache');
        @rmdir($tmpRoot . '/var');
        @rmdir($tmpRoot);
    });
});

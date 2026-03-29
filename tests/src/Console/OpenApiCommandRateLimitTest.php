<?php

declare(strict_types=1);

use Directive\Console\OpenApiCommand;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\RateLimit;
use Directive\Http\Routing\RateLimitKeyType;
use Directive\Http\Routing\VersionStatus;
use Directive\Service\Business\ErrorManager;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Helpers\StubApi;

function rlStubAppIdentity(): AppIdentityConfigInterface
{
    return new class implements AppIdentityConfigInterface {
        public function getAppCode(): string    { return 'test-rl'; }
        public function getAppName(): string    { return 'RateLimitTest'; }
        public function getAppVersion(): string { return '1.0.0'; }
        public function getAppDescription(): string { return ''; }
        public function getAppUrl(): string     { return ''; }
    };
}

describe('OpenApiCommand — x-rate-limit extension', function (): void {

    it('injects x-rate-limit block when Method has an explicit RateLimit', function (): void {
        $rl = new RateLimit(window: 60, maxRequests: 100, keyType: RateLimitKeyType::Ip);

        $manager = new ApiDefinitionManager();
        $domain  = new Domain('items');
        $domain->version('v1', VersionStatus::Open)
            ->service('catalog')
            ->resource('product')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimit($rl)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $outFile = tempnam(sys_get_temp_dir(), 'apisy_rl_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class   => $manager,
            AppIdentityConfigInterface::class => rlStubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);

        expect($tester->getStatusCode())->toBe(0);
        expect($content)->toContain('x-rate-limit');
        expect($content)->toContain('max_requests: 100');
        expect($content)->toContain('window: 60');
        expect($content)->toContain('key_type: ip');

        @unlink($outFile);
    });

    it('does not inject x-rate-limit when Method has no RateLimit', function (): void {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('items');
        $domain->version('v1', VersionStatus::Open)
            ->service('catalog')
            ->resource('product')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $outFile = tempnam(sys_get_temp_dir(), 'apisy_rl2_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class   => $manager,
            AppIdentityConfigInterface::class => rlStubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);

        expect($tester->getStatusCode())->toBe(0);
        expect($content)->not->toContain('x-rate-limit');

        @unlink($outFile);
    });

    it('uses the correct key_type value in x-rate-limit', function (): void {
        $rl = new RateLimit(window: 3600, maxRequests: 10, keyType: RateLimitKeyType::UserId);

        $manager = new ApiDefinitionManager();
        $domain  = new Domain('api');
        $domain->version('v1', VersionStatus::Open)
            ->service('users')
            ->resource('me')
            ->withErrorClass(ErrorManager::class)
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withRateLimit($rl)
            ->get(StubApi::class);
        $manager->registerDomain($domain);

        $outFile = tempnam(sys_get_temp_dir(), 'apisy_rl3_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class       => $manager,
            AppIdentityConfigInterface::class => rlStubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);

        expect($content)->toContain('key_type: user_id');
        expect($content)->toContain('max_requests: 10');
        expect($content)->toContain('window: 3600');

        @unlink($outFile);
    });
});

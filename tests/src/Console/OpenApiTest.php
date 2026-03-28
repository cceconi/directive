<?php

declare(strict_types=1);

use Directive\Console\OpenApiCommand;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\VersionStatus;
use Symfony\Component\Console\Tester\CommandTester;
use Directive\Service\Business\ErrorManager;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubRequestValidator;

function buildOpenApiTree(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('users');
    $domain->version('v1', VersionStatus::Open, 'User management')
        ->service('account')
        ->resource('profile')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class)->withAllowedRoles(['admin'])
        ->put(StubApi::class);

    $domain->version('v2', VersionStatus::Deprecated)
        ->service('account')
        ->resource('profile')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    $manager->registerDomain($domain);
    return $manager;
}

function stubAppIdentity(): AppIdentityConfigInterface
{
    return new class implements AppIdentityConfigInterface {
        public function getAppCode(): string { return 'apisy-test'; }
        public function getAppName(): string { return 'DirectiveTestApp'; }
        public function getAppVersion(): string { return '3.0.0'; }
        public function getAppDescription(): string { return 'Test application'; }
        public function getAppUrl(): string { return ''; }
    };
}

describe('OpenApiCommand', function () {
    it('generates an OpenAPI document with correct structure', function () {
        $manager = buildOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Http\Endpoint\ApiDefinitionManager::class => $manager,
            AppIdentityConfigInterface::class                    => stubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        expect($tester->getStatusCode())->toBe(0);
        expect(file_exists($outFile))->toBeTrue();

        $content = file_get_contents($outFile);
        expect($content)->toContain('openapi: 3.0.3');
        expect($content)->toContain('DirectiveTestApp');
        expect($content)->toContain('/users/v1/account/profile');
        expect($content)->toContain('/users/v2/account/profile');

        @unlink($outFile);
    });

    it('includes security requirement on protected operations', function () {
        $manager = buildOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Http\Endpoint\ApiDefinitionManager::class => $manager,
            AppIdentityConfigInterface::class                    => stubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('bearerAuth');

        @unlink($outFile);
    });

    it('marks deprecated versions in tag descriptions', function () {
        $manager = buildOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Http\Endpoint\ApiDefinitionManager::class => $manager,
            AppIdentityConfigInterface::class                    => stubAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('deprecated');

        @unlink($outFile);
    });
});

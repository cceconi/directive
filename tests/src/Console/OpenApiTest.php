<?php

declare(strict_types=1);

use Directive\Rest\ApiDefinitionManager;
use Directive\Rest\Domain;
use Directive\Rest\VersionStatus;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubPolicy;
use Tests\Helpers\TestConfig;
use Symfony\Component\Console\Tester\CommandTester;
use Directive\Console\OpenApiCommand;

function buildOpenApiTree(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('users');
    $domain->version('v1', VersionStatus::Open, 'User management')
        ->service('account')
        ->resource('profile')
        ->get(StubApi::class, StubPolicy::class)
        ->put(StubApi::class, StubPolicy::class, allowedRoles: ['admin']);

    $domain->version('v2', VersionStatus::Deprecated)
        ->service('account')
        ->resource('profile')
        ->get(StubApi::class, StubPolicy::class);

    $manager->registerDomain($domain);
    return $manager;
}

describe('OpenApiCommand', function () {
    it('generates an OpenAPI document with correct structure', function () {
        $manager  = buildOpenApiTree();
        $config   = new TestConfig();
        $outFile  = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Rest\ApiDefinitionManager::class => $manager,
            \Directive\Service\Configuration\ConfigurationInterface::class => $config,
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
        $manager  = buildOpenApiTree();
        $config   = new TestConfig();
        $outFile  = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Rest\ApiDefinitionManager::class => $manager,
            \Directive\Service\Configuration\ConfigurationInterface::class => $config,
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('bearerAuth');

        @unlink($outFile);
    });

    it('marks deprecated versions in tag descriptions', function () {
        $manager  = buildOpenApiTree();
        $config   = new TestConfig();
        $outFile  = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            \Directive\Rest\ApiDefinitionManager::class => $manager,
            \Directive\Service\Configuration\ConfigurationInterface::class => $config,
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('deprecated');

        @unlink($outFile);
    });
});

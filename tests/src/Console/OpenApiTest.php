<?php

declare(strict_types=1);

use Directive\Console\OpenApiCommand;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\VersionStatus;
use Symfony\Component\Console\Tester\CommandTester;
use Directive\Service\Business\ErrorManager;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Http\Validator\NullRequestValidator;
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
        expect($content)->toContain('openapi: 3.1.0');
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

describe('OpenApiCommand — response codes (task 4.4)', function () {
    it('uses explicit errorCodes on method when set', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('items');
        $domain->version('v1', VersionStatus::Open)
            ->service('catalog')
            ->resource('item')
            ->withRequestValidatorClass(StubRequestValidator::class)
            ->withErrorClass(ErrorManager::class)
            ->get(StubApi::class, errorCodes: [400, 422, 503]);

        $manager->registerDomain($domain);
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('400:');
        expect($content)->toContain('422:');
        expect($content)->toContain('503:');
        expect($content)->toContain('500:');  // always emitted even with explicit list
        expect($content)->not->toContain('404:'); // smart defaults suppressed by explicit list

        @unlink($outFile);
    });

    it('includes 500 in smart defaults when no explicit list', function () {
        $manager = buildOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('500:');

        @unlink($outFile);
    });
});

describe('OpenApiCommand — requestSchema / responseSchema (task 4.5)', function () {
    it('injects $ref in requestBody when requestSchema contains a slash', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('docs');
        $domain->version('v1', VersionStatus::Open)
            ->service('files')
            ->resource('upload')
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withErrorClass(ErrorManager::class)
            ->post(StubApi::class, requestSchema: 'schemas/upload-request.json');

        $manager->registerDomain($domain);
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('schemas/upload-request.json');
        expect($content)->not->toContain('x-schema-class');

        @unlink($outFile);
    });

    it('injects $ref in responses/200 when responseSchema contains a slash', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('catalog');
        $domain->version('v1', VersionStatus::Open)
            ->service('items')
            ->resource('detail')
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withErrorClass(ErrorManager::class)
            ->get(StubApi::class, responseSchema: 'schemas/item-response.json');

        $manager->registerDomain($domain);
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('schemas/item-response.json'); // $ref in responses/200
        expect($content)->not->toContain('x-response-schema-class'); // path-ref, not class-string

        @unlink($outFile);
    });

    it('uses x-schema-class when requestSchema has no slash (class-string)', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('docs');
        $domain->version('v1', VersionStatus::Open)
            ->service('files')
            ->resource('upload')
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withErrorClass(ErrorManager::class)
            ->post(StubApi::class, requestSchema: 'App\\Web\\Request\\UploadRequest');

        $manager->registerDomain($domain);
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('x-schema-class');
        expect($content)->toContain('App\\Web\\Request\\UploadRequest');

        @unlink($outFile);
    });
});

describe('OpenApiCommand — authenticated security flag (task 4.6)', function () {
    it('adds Bearer security when authenticated is true (no allowedRoles required)', function () {
        $manager = new ApiDefinitionManager();
        $domain  = new Domain('account');
        $domain->version('v1', VersionStatus::Open)
            ->service('profile')
            ->resource('me')
            ->withRequestValidatorClass(NullRequestValidator::class)
            ->withErrorClass(ErrorManager::class)
            ->withAuthenticated(true)
            ->get(StubApi::class);

        $manager->registerDomain($domain);
        $outFile = tempnam(sys_get_temp_dir(), 'apisy_oa_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubAppIdentity(),
        ]);

        $tester = new CommandTester(new OpenApiCommand($container));
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        expect($content)->toContain('bearerAuth');
        expect($content)->toContain('401:'); // authenticated → 401 in smart defaults

        @unlink($outFile);
    });
});

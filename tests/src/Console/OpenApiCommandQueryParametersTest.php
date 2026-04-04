<?php

declare(strict_types=1);

use Directive\Console\OpenApiCommand;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Routing\VersionStatus;
use Directive\Http\Validator\PaginationMode;
use Directive\Http\Validator\QueryParametersValidator;
use Directive\Service\AppIdentity\AppIdentityConfigInterface;
use Directive\Service\Business\ErrorManager;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubRequestValidator;

// ------------------------------------------------------------------
// Stub collection validator
// ------------------------------------------------------------------

final class StubCollectionValidator extends QueryParametersValidator
{
    protected function registerQueryParameters(): void
    {
        $this->allowFilter('name');
        $this->allowFilter('status');
        $this->allowSort('name', 'createdAt');
        $this->pagination(PaginationMode::Keyset);
    }
}

// ------------------------------------------------------------------
// Helper
// ------------------------------------------------------------------

function buildQueryParamsOpenApiTree(): ApiDefinitionManager
{
    $manager = new ApiDefinitionManager();
    $domain  = new Domain('items');
    $version = $domain->version('v1', VersionStatus::Open);

    $version->service('catalog')
        ->resource('list')
        ->withRequestValidatorClass(StubCollectionValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    $version->service('single')
        ->resource('item')
        ->withRequestValidatorClass(StubRequestValidator::class)
        ->withErrorClass(ErrorManager::class)
        ->get(StubApi::class);

    $manager->registerDomain($domain);

    return $manager;
}

function stubQueryParamsAppIdentity(): AppIdentityConfigInterface
{
    return new class implements AppIdentityConfigInterface {
        public function getAppCode(): string
        {
            return 'directive-test';
        }
        public function getAppName(): string
        {
            return 'DirectiveTestApp';
        }
        public function getAppVersion(): string
        {
            return '3.0.0';
        }
        public function getAppDescription(): string
        {
            return '';
        }
        public function getAppUrl(): string
        {
            return '';
        }
    };
}

// ------------------------------------------------------------------
// 5.6 — OpenApiCommand generates parameters for QueryParametersValidator
// ------------------------------------------------------------------

describe('OpenApiCommand — QueryParametersValidator integration', function () {
    it('generates parameters block for endpoint using QueryParametersValidator', function () {
        $manager = buildQueryParamsOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'directive_qpv_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubQueryParamsAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        expect($tester->getStatusCode())->toBe(0);

        $content = file_get_contents($outFile);
        assert(is_string($content));

        // Collection endpoint should have parameters
        expect($content)->toContain('filter[name]');
        expect($content)->toContain('filter[status]');
        expect($content)->toContain('sort[name]');
        expect($content)->toContain('sort[createdAt]');
        expect($content)->toContain('page[after]');
        expect($content)->toContain('page[before]');

        @unlink($outFile);
    });

    it('does not generate parameters block for standard AbstractRequestValidator endpoint', function () {
        $manager = buildQueryParamsOpenApiTree();
        $outFile = tempnam(sys_get_temp_dir(), 'directive_qpv_') . '.yaml';

        $container = $this->container([
            ApiDefinitionManager::class      => $manager,
            AppIdentityConfigInterface::class => stubQueryParamsAppIdentity(),
        ]);

        $command = new OpenApiCommand($container);
        $tester  = new CommandTester($command);
        $tester->execute(['--output' => $outFile]);

        $content = file_get_contents($outFile);
        assert(is_string($content));

        // We can check the /items/v1/catalog/item endpoint does NOT have 'filter[' nearby
        // by parsing the YAML to confirm no parameters key for that path
        $yaml = \Symfony\Component\Yaml\Yaml::parse($content);
        assert(is_array($yaml));

        /** @var array<string, mixed> $paths */
        $paths = $yaml['paths'] ?? [];

        /** @var array<string, mixed> $itemPath */
        $itemPath = $paths['/items/v1/single/item'] ?? [];

        /** @var array<string, mixed> $getOp */
        $getOp = $itemPath['get'] ?? [];

        expect(isset($getOp['parameters']))->toBeFalse();

        @unlink($outFile);
    });
});

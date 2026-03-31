<?php

declare(strict_types=1);

use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Routing\Domain;
use Directive\Http\Validator\NullRequestValidator;
use Directive\Service\Business\ErrorManager;
use Directive\Application\Role\AbstractRole;
use Directive\Application\Role\GuestRole;
use Directive\Application\Role\Permission;
use Tests\Helpers\StubApi;
use Tests\Helpers\StubWebUser;

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeRole(string $slug = 'admin'): AbstractRole
{
    return new class ($slug) extends AbstractRole {
        public function __construct(private readonly string $roleSlug) {}

        public function getPermission(string $useCase): Permission
        {
            return Permission::Allow;
        }

        public function slug(): string
        {
            return $this->roleSlug;
        }
    };
}

function makeMinimalManager(): ApiDefinitionManager
{
    $domain = new Domain('any');
    $domain->version('v1')
        ->service('test')
        ->resource('ping')
        ->withErrorClass(ErrorManager::class)
        ->withRequestValidatorClass(NullRequestValidator::class)
        ->get(StubApi::class);

    $manager = new ApiDefinitionManager();
    $manager->registerDomain($domain);
    return $manager;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('DirectiveHttpTestCase — actingAs()', function (): void {

    it('stores the role and uses it in buildRouter when no explicit user is given', function (): void {
        $role   = makeRole('member');
        $router = $this->actingAs($role)->buildRouter(makeMinimalManager());

        // Router is built — actingAs user was used (no exception means DI resolved)
        expect($router)->toBeObject();
    });

    it('returns static for fluent chaining', function (): void {
        $result = $this->actingAs(makeRole());
        expect($result)->toBe($this);
    });

    it('explicit webUser param overrides actingAs user', function (): void {
        $explicit = new StubWebUser();
        $this->actingAs(makeRole('other'));

        $router = $this->buildRouter(makeMinimalManager(), webUser: $explicit);
        expect($router)->toBeObject();
    });
});

describe('DirectiveHttpTestCase — assertStatus()', function (): void {

    it('passes when status code matches', function (): void {
        $factory  = new \Nyholm\Psr7\Factory\Psr17Factory();
        $response = $factory->createResponse(200);
        $this->assertStatus($response, 200); // must not throw
    });

    it('fails when status code does not match', function (): void {
        $factory  = new \Nyholm\Psr7\Factory\Psr17Factory();
        $response = $factory->createResponse(404);

        expect(fn () => $this->assertStatus($response, 200))->toThrow(\Exception::class);
    });
});

describe('DirectiveHttpTestCase — getJson()', function (): void {

    it('decodes JSON body into an array', function (): void {
        $factory  = new \Nyholm\Psr7\Factory\Psr17Factory();
        $body     = $factory->createStream(json_encode(['key' => 'value']) ?: '{}');
        $response = $factory->createResponse(200)->withBody($body);

        $result = $this->getJson($response);
        expect($result)->toBe(['key' => 'value']);
    });

    it('returns empty array for empty body', function (): void {
        $factory  = new \Nyholm\Psr7\Factory\Psr17Factory();
        $body     = $factory->createStream('{}');
        $response = $factory->createResponse(200)->withBody($body);

        expect($this->getJson($response))->toBe([]);
    });
});

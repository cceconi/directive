<?php

declare(strict_types=1);

namespace Tests\Helpers;

use DI\ContainerBuilder;
use Directive\Http\Endpoint\ApiDefinitionManager;
use Directive\Http\Response\HttpResponse;
use Directive\Http\Router;
use Directive\Service\Business\ErrorInterface;
use Directive\Service\Business\ErrorManager;
use Directive\Application\Role\AbstractRole;
use Directive\Service\Security\WebUserInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test helpers injected into all Pest test closures via uses() in tests/Pest.php.
 *
 * Must be a trait so Pest 3 properly mixes them into $this.
 */
trait DirectiveHttpTestCase
{
    private ?WebUserInterface $currentUser = null;
    // ------------------------------------------------------------------
    // Auth helper
    // ------------------------------------------------------------------

    public function actingAs(AbstractRole $role): static
    {
        $this->currentUser = (new StubWebUser())->withRole($role);
        return $this;
    }

    // ------------------------------------------------------------------
    // Request builders
    // ------------------------------------------------------------------

    public function createRequest(
        string $method,
        string $uri,
        array $body = [],
        array $headers = [],
    ): ServerRequestInterface {
        $request = new ServerRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== []) {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    public function createJsonRequest(string $method, string $uri, array $body = []): ServerRequestInterface
    {
        return $this->createRequest($method, $uri, $body, ['Content-Type' => 'application/json']);
    }

    // ------------------------------------------------------------------
    // Router builder
    // ------------------------------------------------------------------

    /**
     * Build a real Router wired with the provided ApiDefinitionManager.
     *
     * @param array<string, mixed> $extraDefinitions
     */
    public function buildRouter(
        ApiDefinitionManager $manager,
        ?WebUserInterface $webUser = null,
        array $extraDefinitions = [],
    ): Router {
        $factory  = new Psr17Factory();
        $httpResp = new HttpResponse($factory, $factory);
        $user     = $webUser ?? $this->currentUser ?? new StubWebUser();

        $builder = new ContainerBuilder();
        $builder->addDefinitions(array_merge(
            [ErrorInterface::class => \DI\factory(fn () => new ErrorManager())],
            $extraDefinitions,
        ));
        $container = $builder->build();

        return new Router($manager, $httpResp, $container, $user);
    }

    public function dispatch(
        Router $router,
        string $method,
        string $domain,
        string $version,
        string $service = '',
        string $resource = '',
        array $body = [],
    ): ResponseInterface {
        $factory = new Psr17Factory();

        if ($service === '') {
            $uri  = "/{$domain}/{$version}/{$resource}";
            $args = [
                'domain'   => $domain,
                'version'  => $version,
                'resource' => $resource,
            ];
        } else {
            $uri  = "/{$domain}/{$version}/{$service}/{$resource}";
            $args = [
                'domain'   => $domain,
                'version'  => $version,
                'service'  => $service,
                'resource' => $resource,
            ];
        }

        $request = $this->createJsonRequest($method, $uri, $body);

        return $router->resolve($request, $factory->createResponse(), $args);
    }

    // ------------------------------------------------------------------
    // Response assertions
    // ------------------------------------------------------------------

    public function assertResponseStatus(ResponseInterface $response, int $expected): void
    {
        expect($response->getStatusCode())->toBe($expected);
    }

    public function assertStatus(ResponseInterface $response, int $expected): void
    {
        $this->assertResponseStatus($response, $expected);
    }

    /**
     * @return array<mixed>
     */
    public function getJson(ResponseInterface $response): array
    {
        return $this->getBodyArray($response);
    }

    public function assertJsonBody(ResponseInterface $response, string $key, mixed $expected): void
    {
        $body = json_decode((string) $response->getBody(), true);
        expect($body[$key] ?? null)->toBe($expected);
    }

    public function assertJsonDataContains(ResponseInterface $response, string $key, mixed $expected): void
    {
        $body = json_decode((string) $response->getBody(), associative: true) ?? [];
        $data = $body['data'] ?? [];
        expect($data[$key] ?? null)->toBe($expected);
    }

    /**
     * @return array<mixed>
     */
    public function getBodyArray(ResponseInterface $response): array
    {
        return json_decode((string) $response->getBody(), associative: true) ?? [];
    }

    // ------------------------------------------------------------------
    // DI container builder
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $definitions
     */
    public function container(array $definitions = []): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions($definitions);
        return $builder->build();
    }
}

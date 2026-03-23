<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\BadRequestException;
use Directive\Exception\UnprocessableException;
use Directive\Exception\ForbiddenException;
use Directive\Exception\GoneException;
use Directive\Exception\MethodNotAllowedException;
use Directive\Exception\NotFoundException;
use Directive\Exception\UnauthorizedException;
use Directive\Service\Security\Role\GuestRole;
use Directive\Service\Security\WebUserInterface;
use Directive\Web\ResponseEntity;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Core request dispatcher.
 *
 * Given a PSR-7 request with {domain}/{version}/{service}/{resource} args,
 * the Router:
 *   1. Locates the Method in the ApiDefinitionManager tree.
 *   2. Validates the CORS origin.
 *   3. Checks the current user's role against allowed roles (UCAC).
 *   4. Runs the Policy to validate/clean input.
 *   5. Instantiates and runs the Api handler.
 *   6. Returns the formatted PSR-7 response via HttpResponse.
 *
 * All typed exceptions are caught here and mapped to the correct HTTP codes.
 */
final class Router
{
    /**
     * @param array<string> $globalCorsOrigins Allowed CORS origins from configuration.
     */
    public function __construct(
        private readonly ApiDefinitionManager $manager,
        private readonly HttpResponse $httpResponse,
        private readonly ContainerInterface $container,
        private readonly WebUserInterface $webUser,
        private readonly array $globalCorsOrigins = [],
    ) {}

    // ------------------------------------------------------------------
    // Main dispatch
    // ------------------------------------------------------------------

    /**
     * @param array<string, string> $args Route args: domain, version, service, resource.
     */
    public function resolve(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $route  = $this->buildRoute($args);
        $method = $request->getMethod();

        try {
            return $this->dispatch($request, $route, $args);
        } catch (BadRequestException $e) {
            return $this->httpResponse->badRequest($route, $method, $e->getErrors());
        } catch (UnauthorizedException) {
            return $this->httpResponse->unauthorized($route, $method);
        } catch (ForbiddenException) {
            return $this->httpResponse->forbidden($route, $method);
        } catch (NotFoundException) {
            return $this->httpResponse->notFound($route, $method);
        } catch (MethodNotAllowedException) {
            return $this->httpResponse->methodNotAllowed($route, $method);
        } catch (GoneException) {
            return $this->httpResponse->gone($route, $method);
        } catch (UnprocessableException $e) {
            return $this->httpResponse->unprocessable($route, $method, $e->getErrors());
        } catch (\Throwable) {
            return $this->httpResponse->internalError($route, $method);
        }
    }

    /**
     * Handle a CORS OPTIONS preflight.
     *
     * @param array<string, string> $args
     */
    public function resolveOptions(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args,
    ): ResponseInterface {
        $route  = $this->buildRoute($args);
        $method = $request->getMethod();

        try {
            $resource = $this->findResource($args);

            $allowedMethods = implode(', ', array_merge($resource->getMethodNames(), ['OPTIONS']));

            return $response
                ->withStatus(204)
                ->withHeader('Allow', $allowedMethods)
                ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        } catch (NotFoundException) {
            return $this->httpResponse->notFound($route, $method);
        } catch (GoneException) {
            return $this->httpResponse->gone($route, $method);
        }
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @param array<string, string> $args
     * @throws NotFoundException
     * @throws GoneException
     * @throws MethodNotAllowedException
     * @throws ForbiddenException
     * @throws UnauthorizedException
     * @throws BadRequestException
     * @throws UnprocessableException
     */
    private function dispatch(
        ServerRequestInterface $request,
        string $route,
        array $args,
    ): ResponseInterface {
        // 1. Locate method in tree
        $httpMethod  = $request->getMethod();
        $resource    = $this->findResource($args);
        $methodDef   = $resource->findMethod($httpMethod);

        // 2. Validate CORS origin
        $this->checkCors($request, $methodDef);

        // 3. Check user role
        $this->checkRole($methodDef);

        // 4. Run request validator
        /** @var RequestValidatorInterface $policy */
        $policy = $this->container->get($methodDef->policyClass);
        $policy->setRequest($request);
        $requestEntity = $policy->getRequestEntity();

        if ($policy->hasErrors()) {
            throw new BadRequestException('Validation failed.')->withErrors($policy->getErrors());
        }

        // 5. Resolve response entity class
        $responseEntityClass = $methodDef->responseEntityClass ?? ResponseEntity::class;
        /** @var ResponseEntity $responseEntity */
        $responseEntity = new $responseEntityClass();

        // 6. Run API handler
        /** @var ApiInterface $api */
        $api = new $methodDef->apiClass($this->container, $responseEntity, $requestEntity);
        $api->run();

        return $this->httpResponse->ok($api->getResponseEntity(), $route, $httpMethod);
    }

    /**
     * @param array<string, string> $args
     * @throws NotFoundException
     * @throws GoneException
     */
    private function findResource(array $args): Resource
    {
        $domain  = $this->manager->findDomain($args['domain'] ?? '');
        $version = $domain->findVersion($args['version'] ?? '');
        $version->checkAvailability();
        $service = $version->findService($args['service'] ?? '');

        return $service->findResource($args['resource'] ?? '');
    }

    /**
     * @throws ForbiddenException
     */
    private function checkCors(ServerRequestInterface $request, Method $method): void
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return;
        }

        $allowed = $method->allowCors !== [] ? $method->allowCors : $this->globalCorsOrigins;

        if ($allowed === [] || in_array('*', $allowed, true) || in_array($origin, $allowed, true)) {
            return;
        }

        throw new ForbiddenException(sprintf('Origin "%s" is not allowed.', $origin));
    }

    /**
     * @throws UnauthorizedException
     * @throws ForbiddenException
     */
    private function checkRole(Method $method): void
    {
        if ($method->allowedRoles === []) {
            return;
        }

        if ($this->webUser->getRole() instanceof GuestRole) {
            throw new UnauthorizedException('Authentication required.');
        }

        $slug = $this->webUser->getRole()->slug();

        if (!in_array($slug, $method->allowedRoles, true)) {
            throw new ForbiddenException(
                sprintf('Role "%s" is not allowed on this route.', $slug),
            );
        }
    }

    /** @param array<string, string> $args */
    private function buildRoute(array $args): string
    {
        return sprintf(
            '/%s/%s/%s/%s',
            $args['domain'] ?? '',
            $args['version'] ?? '',
            $args['service'] ?? '',
            $args['resource'] ?? '',
        );
    }
}

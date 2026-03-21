<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\MethodNotAllowedException;

/**
 * A named resource within a Service.
 * Holds one Method per HTTP verb.
 *
 * Fluent usage:
 *   $service->resource('profile')
 *       ->get(GetProfile::class, policyClass: GetProfilePolicy::class, allowedRoles: ['user'])
 *       ->put(PutProfile::class, policyClass: PutProfilePolicy::class, allowedRoles: ['user']);
 */
class Resource
{
    /** @var array<string, Method> */
    private array $methods = [];

    public function __construct(
        public private(set) readonly string $name,
    ) {}

    // ------------------------------------------------------------------
    // HTTP method shortcuts — each returns $this for chaining
    // ------------------------------------------------------------------

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function get(
        string $apiClass,
        string $policyClass,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('GET', $apiClass, $policyClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function post(
        string $apiClass,
        string $policyClass,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('POST', $apiClass, $policyClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function put(
        string $apiClass,
        string $policyClass,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('PUT', $apiClass, $policyClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function patch(
        string $apiClass,
        string $policyClass,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('PATCH', $apiClass, $policyClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function delete(
        string $apiClass,
        string $policyClass,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('DELETE', $apiClass, $policyClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    // ------------------------------------------------------------------
    // Router API
    // ------------------------------------------------------------------

    /**
     * @throws MethodNotAllowedException
     */
    public function findMethod(string $httpMethod): Method
    {
        $key = strtoupper($httpMethod);

        return $this->methods[$key] ?? throw new MethodNotAllowedException(
            sprintf('Method %s is not allowed on resource "%s".', $key, $this->name),
        );
    }

    /** @return array<string> List of registered HTTP method names (for OPTIONS responses). */
    public function getMethodNames(): array
    {
        return array_keys($this->methods);
    }

    /**
     * Return all registered methods (used by the OpenAPI exporter).
     *
     * @return array<string, Method>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    /**
     * @param class-string      $apiClass
     * @param class-string      $policyClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    private function addMethod(
        string $httpMethod,
        string $apiClass,
        string $policyClass,
        array $allowedRoles,
        array $allowCors,
        ?string $responseEntityClass,
        ?string $requestEntityClass,
    ): static {
        $key = strtoupper($httpMethod);

        if (isset($this->methods[$key])) {
            throw new ApiDefinitionException(
                sprintf('Method %s is already registered on resource "%s".', $key, $this->name),
            );
        }

        $this->methods[$key] = new Method(
            httpMethod: $key,
            apiClass: $apiClass,
            policyClass: $policyClass,
            allowedRoles: $allowedRoles,
            allowCors: $allowCors,
            responseEntityClass: $responseEntityClass,
            requestEntityClass: $requestEntityClass,
        );

        return $this;
    }
}

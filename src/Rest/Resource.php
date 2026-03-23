<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Exception\ApiDefinitionException;
use Directive\Exception\MethodNotAllowedException;
use Directive\Service\Business\ErrorManager;

/**
 * A named resource within a Service.
 * Holds one Method per HTTP verb.
 *
 * Fluent usage:
 *   $service->resource('profile')
 *       ->get(GetProfile::class, requestValidatorClass: GetProfilePolicy::class, errorClass: ErrorManager::class, allowedRoles: ['user'])
 *       ->put(PutProfile::class, requestValidatorClass: PutProfilePolicy::class, errorClass: ErrorManager::class, allowedRoles: ['user']);
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
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function get(
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass = ErrorManager::class,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('GET', $apiClass, $requestValidatorClass, $errorClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function post(
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass = ErrorManager::class,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('POST', $apiClass, $requestValidatorClass, $errorClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function put(
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass = ErrorManager::class,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('PUT', $apiClass, $requestValidatorClass, $errorClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function patch(
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass = ErrorManager::class,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('PATCH', $apiClass, $requestValidatorClass, $errorClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
    }

    /**
     * @param class-string      $apiClass
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function delete(
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass = ErrorManager::class,
        array $allowedRoles = [],
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
    ): static {
        return $this->addMethod('DELETE', $apiClass, $requestValidatorClass, $errorClass, $allowedRoles, $allowCors, $responseEntityClass, $requestEntityClass);
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
     * @param class-string      $requestValidatorClass
     * @param class-string      $errorClass
     * @param array<string>     $allowedRoles
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    private function addMethod(
        string $httpMethod,
        string $apiClass,
        string $requestValidatorClass,
        string $errorClass,
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
            requestValidatorClass: $requestValidatorClass,
            errorClass: $errorClass,
            allowedRoles: $allowedRoles,
            allowCors: $allowCors,
            responseEntityClass: $responseEntityClass,
            requestEntityClass: $requestEntityClass,
        );

        return $this;
    }
}

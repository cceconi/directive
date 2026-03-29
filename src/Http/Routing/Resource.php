<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

use Directive\Http\Validator\NullRequestValidator;
use Directive\Http\Exception\ApiDefinitionException;
use Directive\Http\Exception\MethodNotAllowedException;
use Directive\Service\Business\ErrorManager;

/**
 * A named resource within a Service.
 * Holds one Method per HTTP verb.
 *
 * Defaults are configured via the with* fluent methods (mutation semantics — returns $this):
 *   $service->resource('profile')
 *       ->withAllowedRoles(['user'])
 *       ->get(GetProfile::class)
 *       ->put(PutProfile::class);
 *
 * withErrorClass/withRequestValidatorClass/withAllowedRoles update $this in-place and
 * return $this. This is intentional: Resource is a mutable builder, and Service holds
 * a reference to the same object. A clone would orphan methods added after the with* call.
 */
class Resource
{
    /** @var array<string, Method> */
    private array $methods = [];

    public function __construct(
        public private(set) readonly string $name,
        private MethodDefaults $defaults = new MethodDefaults(),
    ) {}

    // ------------------------------------------------------------------
    // Fluent defaults (mutation semantics — returns $this)
    // ------------------------------------------------------------------

    /** @param class-string $class */
    public function withErrorClass(string $class): static
    {
        $this->defaults = $this->defaults->withErrorClass($class);
        return $this;
    }

    /** @param class-string $class */
    public function withRequestValidatorClass(string $class): static
    {
        $this->defaults = $this->defaults->withRequestValidatorClass($class);
        return $this;
    }

    /** @param array<string> $roles */
    public function withAllowedRoles(array $roles): static
    {
        $this->defaults = $this->defaults->withAllowedRoles($roles);
        return $this;
    }

    /** @param array<int> $codes */
    public function withErrorCodes(array $codes): static
    {
        $this->defaults = $this->defaults->withErrorCodes($codes);
        return $this;
    }

    public function withAuthenticated(bool $authenticated): static
    {
        $this->defaults = $this->defaults->withAuthenticated($authenticated);
        return $this;
    }

    // ------------------------------------------------------------------
    // HTTP method shortcuts — each returns $this for chaining
    // ------------------------------------------------------------------

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes         If null, inherits from cascaded defaults
     */
    public function get(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
    ): static {
        return $this->addMethod('GET', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $requestSchema, $responseSchema, $errorCodes, $authenticated);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes         If null, inherits from cascaded defaults
     */
    public function post(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
    ): static {
        return $this->addMethod('POST', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $requestSchema, $responseSchema, $errorCodes, $authenticated);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes         If null, inherits from cascaded defaults
     */
    public function put(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
    ): static {
        return $this->addMethod('PUT', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $requestSchema, $responseSchema, $errorCodes, $authenticated);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes         If null, inherits from cascaded defaults
     */
    public function patch(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
    ): static {
        return $this->addMethod('PATCH', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $requestSchema, $responseSchema, $errorCodes, $authenticated);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes         If null, inherits from cascaded defaults
     */
    public function delete(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
    ): static {
        return $this->addMethod('DELETE', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $requestSchema, $responseSchema, $errorCodes, $authenticated);
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
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     * @param array<int>|null   $errorCodes          Per-call override; null falls through to defaults
     */
    private function addMethod(
        string $httpMethod,
        string $apiClass,
        array $allowCors,
        ?string $responseEntityClass,
        ?string $requestEntityClass,
        ?string $requestSchema = null,
        ?string $responseSchema = null,
        ?array $errorCodes = null,
        ?bool $authenticated = null,
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
            requestValidatorClass: $this->defaults->requestValidatorClass ?? NullRequestValidator::class,
            errorClass: $this->defaults->errorClass ?? ErrorManager::class,
            allowedRoles: $this->defaults->allowedRoles ?? [],
            allowCors: $allowCors,
            responseEntityClass: $responseEntityClass,
            requestEntityClass: $requestEntityClass,
            requestSchema: $requestSchema,
            responseSchema: $responseSchema,
            errorCodes: $errorCodes ?? $this->defaults->errorCodes ?? [],
            authenticated: $authenticated ?? $this->defaults->authenticated ?? false,
        );

        return $this;
    }
}

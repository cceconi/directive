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

    public function withRateLimit(RateLimit $rateLimit): static
    {
        $this->defaults = $this->defaults->withRateLimit($rateLimit);
        return $this;
    }

    public function withRateLimitKeyType(RateLimitKeyType $rateLimitKeyType): static
    {
        $this->defaults = $this->defaults->withRateLimitKeyType($rateLimitKeyType);
        return $this;
    }

    public function withRateLimitEnabled(bool $rateLimitEnabled): static
    {
        $this->defaults = $this->defaults->withRateLimitEnabled($rateLimitEnabled);
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
     */
    public function get(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod('GET', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $overrides);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function post(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod('POST', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $overrides);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function put(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod('PUT', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $overrides);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function patch(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod('PATCH', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $overrides);
    }

    /**
     * @param class-string      $apiClass
     * @param array<string>     $allowCors
     * @param class-string|null $responseEntityClass
     * @param class-string|null $requestEntityClass
     */
    public function delete(
        string $apiClass,
        array $allowCors = [],
        ?string $responseEntityClass = null,
        ?string $requestEntityClass = null,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod('DELETE', $apiClass, $allowCors, $responseEntityClass, $requestEntityClass, $overrides);
    }

    /**
     * Register any HTTP verb not covered by the named shortcuts.
     *
     * @param class-string $apiClass
     */
    public function withMethod(
        string $httpMethod,
        string $apiClass,
        ?MethodDefaults $overrides = null,
    ): static {
        return $this->addMethod(strtoupper($httpMethod), $apiClass, [], null, null, $overrides);
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
     */
    private function addMethod(
        string $httpMethod,
        string $apiClass,
        array $allowCors,
        ?string $responseEntityClass,
        ?string $requestEntityClass,
        ?MethodDefaults $overrides = null,
    ): static {
        $key = strtoupper($httpMethod);

        if (isset($this->methods[$key])) {
            throw new ApiDefinitionException(
                sprintf('Method %s is already registered on resource "%s".', $key, $this->name),
            );
        }

        $resolved = $this->resolveDefaults($overrides);

        $this->methods[$key] = new Method(
            httpMethod: $key,
            apiClass: $apiClass,
            requestValidatorClass: $resolved->requestValidatorClass ?? NullRequestValidator::class,
            errorClass: $resolved->errorClass ?? ErrorManager::class,
            allowedRoles: $resolved->allowedRoles ?? [],
            allowCors: $allowCors,
            responseEntityClass: $responseEntityClass,
            requestEntityClass: $requestEntityClass,
            requestSchema: $resolved->requestSchema,
            responseSchema: $resolved->responseSchema,
            errorCodes: $resolved->errorCodes ?? [],
            authenticated: $resolved->authenticated ?? false,
            rateLimit: $resolved->rateLimit,
            rateLimitKeyType: $resolved->rateLimitKeyType,
            rateLimitEnabled: $resolved->rateLimitEnabled,
        );

        return $this;
    }

    private function resolveDefaults(?MethodDefaults $overrides): MethodDefaults
    {
        if ($overrides === null) {
            return $this->defaults;
        }

        return new MethodDefaults(
            errorClass:            $overrides->errorClass            ?? $this->defaults->errorClass,
            requestValidatorClass: $overrides->requestValidatorClass ?? $this->defaults->requestValidatorClass,
            allowedRoles:          $overrides->allowedRoles          ?? $this->defaults->allowedRoles,
            errorCodes:            $overrides->errorCodes            ?? $this->defaults->errorCodes,
            authenticated:         $overrides->authenticated         ?? $this->defaults->authenticated,
            rateLimit:             $overrides->rateLimit             ?? $this->defaults->rateLimit,
            rateLimitKeyType:      $overrides->rateLimitKeyType      ?? $this->defaults->rateLimitKeyType,
            rateLimitEnabled:      $overrides->rateLimitEnabled      ?? $this->defaults->rateLimitEnabled,
            requestSchema:         $overrides->requestSchema         ?? $this->defaults->requestSchema,
            responseSchema:        $overrides->responseSchema        ?? $this->defaults->responseSchema,
        );
    }
}

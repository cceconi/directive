<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

/**
 * Immutable value object carrying cascading defaults for the API tree.
 *
 * A null field means "not set at this level — fall through to parent or framework default".
 * An explicit value (including an empty array for allowedRoles) stops the fall-through.
 *
 * allowedRoles uses strict replacement semantics (never merge):
 * the most specific declaration wins entirely. Setting allowedRoles = [] means
 * "public route, no role check" — it is NOT the same as null.
 */
readonly class MethodDefaults
{
    /**
     * @param class-string|null  $errorClass
     * @param class-string|null  $requestValidatorClass
     * @param array<string>|null $allowedRoles  null = fall through; [] = public route
     * @param array<int>|null    $errorCodes    null = fall through; [] = use auto-detection in OpenApiCommand
     * @param bool|null          $authenticated null = fall through; true = force Bearer auth in OpenAPI
     */
    public function __construct(
        public ?string $errorClass = null,
        public ?string $requestValidatorClass = null,
        public ?array $allowedRoles = null,
        public ?array $errorCodes = null,
        public ?bool $authenticated = null,
    ) {}

    /** @param class-string $class */
    public function withErrorClass(string $class): self
    {
        return new self($class, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $this->authenticated);
    }

    /** @param class-string $class */
    public function withRequestValidatorClass(string $class): self
    {
        return new self($this->errorClass, $class, $this->allowedRoles, $this->errorCodes, $this->authenticated);
    }

    /**
     * Strict replacement — never merges with parent allowedRoles.
     *
     * @param array<string> $roles
     */
    public function withAllowedRoles(array $roles): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $roles, $this->errorCodes, $this->authenticated);
    }

    /**
     * Strict replacement — never merges with parent errorCodes.
     *
     * @param array<int> $codes
     */
    public function withErrorCodes(array $codes): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $codes, $this->authenticated);
    }

    /**
     * Fall-through semantics — null means "not declared at this level".
     */
    public function withAuthenticated(bool $authenticated): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $authenticated);
    }
}

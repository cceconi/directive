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
     * @param array<string>|null $allowedRoles    null = fall through; [] = public route
     * @param array<int>|null    $errorCodes      null = fall through; [] = use auto-detection in OpenApiCommand
     * @param bool|null          $authenticated   null = fall through; true = force Bearer auth in OpenAPI
     * @param RateLimit|null     $rateLimit       null = fall through to global RateLimitConfigInterface defaults
     * @param RateLimitKeyType|null $rateLimitKeyType null = fall through; overrides rateLimit->keyType when set
     * @param bool|null          $rateLimitEnabled null = fall through; false = subtree opt-out; true = force-enable
     */
    public function __construct(
        public ?string $errorClass = null,
        public ?string $requestValidatorClass = null,
        public ?array $allowedRoles = null,
        public ?array $errorCodes = null,
        public ?bool $authenticated = null,
        public ?RateLimit $rateLimit = null,
        public ?RateLimitKeyType $rateLimitKeyType = null,
        public ?bool $rateLimitEnabled = null,
    ) {}

    /** @param class-string $class */
    public function withErrorClass(string $class): self
    {
        return new self($class, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $this->authenticated, $this->rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /** @param class-string $class */
    public function withRequestValidatorClass(string $class): self
    {
        return new self($this->errorClass, $class, $this->allowedRoles, $this->errorCodes, $this->authenticated, $this->rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Strict replacement — never merges with parent allowedRoles.
     *
     * @param array<string> $roles
     */
    public function withAllowedRoles(array $roles): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $roles, $this->errorCodes, $this->authenticated, $this->rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Strict replacement — never merges with parent errorCodes.
     *
     * @param array<int> $codes
     */
    public function withErrorCodes(array $codes): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $codes, $this->authenticated, $this->rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Fall-through semantics — null means "not declared at this level".
     */
    public function withAuthenticated(bool $authenticated): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $authenticated, $this->rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Set a local rate limit override for this subtree.
     * Replaces global RateLimitConfigInterface defaults for all routes in the subtree.
     */
    public function withRateLimit(RateLimit $rateLimit): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $this->authenticated, $rateLimit, $this->rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Override the key type strategy for this subtree.
     * Takes precedence over rateLimit->keyType when set.
     */
    public function withRateLimitKeyType(RateLimitKeyType $rateLimitKeyType): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $this->authenticated, $this->rateLimit, $rateLimitKeyType, $this->rateLimitEnabled);
    }

    /**
     * Enable or disable rate limiting for this subtree.
     * false = opt-out (even if global feature flag is on).
     * true  = force-enable (even if global feature flag is off).
     */
    public function withRateLimitEnabled(bool $rateLimitEnabled): self
    {
        return new self($this->errorClass, $this->requestValidatorClass, $this->allowedRoles, $this->errorCodes, $this->authenticated, $this->rateLimit, $this->rateLimitKeyType, $rateLimitEnabled);
    }
}

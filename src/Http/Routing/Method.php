<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

/**
 * Leaf node of the API tree.
 *
 * Represents one HTTP method on one Resource, binding together:
 *   - the Api handler class (implements ApiInterface, Epic 4)
 *   - the validator class   (extends AbstractRequestValidator)
 *   - the allowed role slugs (UCAC)
 *   - optional CORS origins, request/response entity classes
 *
 * PHP 8.4 readonly class: all properties are immutable after construction.
 */
readonly class Method
{
    /**
     * @param class-string        $apiClass              Api handler class
     * @param class-string        $requestValidatorClass Request validator class (extends AbstractRequestValidator)
     * @param class-string        $errorClass            Error manager class
     * @param array<string>       $allowedRoles          Allowed role slugs (empty = public, no auth check)
     * @param array<string>       $allowCors             Allowed CORS origins (empty = use global config)
     * @param class-string|null   $responseEntityClass   Custom ResponseEntity class
     * @param class-string|null   $requestEntityClass    Custom RequestEntity class
     * @param string|null         $requestSchema         Request schema ref: class-string or external path (e.g. 'schemas/foo.json')
     * @param string|null         $responseSchema        Response schema ref: class-string or external path
     * @param array<int>          $errorCodes            Explicit HTTP error codes for this endpoint (overrides auto-detection)
     * @param bool                $authenticated         Force Bearer auth in OpenAPI even when allowedRoles is empty
     */
    public function __construct(
        public string $httpMethod,
        public string $apiClass,
        public string $requestValidatorClass,
        public string $errorClass,
        public array $allowedRoles = [],
        public array $allowCors = [],
        public ?string $responseEntityClass = null,
        public ?string $requestEntityClass = null,
        public ?string $requestSchema = null,
        public ?string $responseSchema = null,
        public array $errorCodes = [],
        public bool $authenticated = false,
    ) {}
}

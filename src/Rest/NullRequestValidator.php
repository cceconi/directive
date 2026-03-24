<?php

declare(strict_types=1);

namespace Directive\Rest;

use Directive\Web\RequestEntity;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Framework-level pass-through request validator.
 *
 * Used when no requestValidatorClass is declared anywhere in the API tree.
 * Always accepts the request without validation, returns an empty RequestEntity.
 *
 * @todo C-04 — When AbstractRequestValidator lands, make this extend it instead.
 */
final class NullRequestValidator implements RequestValidatorInterface
{
    public function setRequest(ServerRequestInterface $request): void
    {
        // No-op — NullRequestValidator does not inspect the request.
    }

    public function getRequestEntity(): RequestEntity
    {
        return new class extends RequestEntity {};
    }

    public function hasErrors(): bool
    {
        return false;
    }

    /** @return array<array<string, string>> */
    public function getErrors(): array
    {
        return [];
    }
}

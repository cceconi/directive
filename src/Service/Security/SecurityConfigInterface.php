<?php

declare(strict_types=1);

namespace Directive\Service\Security;

interface SecurityConfigInterface
{
    public function getTokenLifetime(): int;
    public function getCookieDomain(): string;
    public function isCookieHttpOnly(): bool;
    public function getCookieSameSite(): string;
    public function isHttpSecure(): bool;
    /** @return array<string> */
    public function getHttpRelaxedHosts(): array;
    /** @return array<string> */
    public function getCorsAllowedOrigins(): array;
    public function getAppUrl(): string;
    /** @return array<string, string> */
    public function getSecurityHeaderOverrides(): array;
}

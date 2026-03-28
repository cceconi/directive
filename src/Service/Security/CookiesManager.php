<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use DateTime;
use Dflydev\FigCookies\FigRequestCookies;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Directive\Service\Security\SecurityConfigInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Manages reading cookies from requests and writing them back to responses.
 * Uses dflydev/fig-cookies v3.
 */
final class CookiesManager implements CookiesManagerInterface
{
    /** @var array<string, SetCookie> */
    private array $responseCookies = [];

    public function __construct(
        private readonly SecurityConfigInterface $config,
    ) {}

    // ------------------------------------------------------------------
    // CookiesManagerInterface
    // ------------------------------------------------------------------

    public function storeFromRequest(ServerRequestInterface $request): void
    {
        // In dflydev/fig-cookies v3, cookie values come directly from PSR-7 getCookieParams()
        // No separate "store" mechanism needed — cookies are read from request on demand.
        // This method is kept for compatibility with the middleware pipeline.
    }

    public function setResponseCookies(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->responseCookies as $setCookie) {
            $response = FigResponseCookies::set($response, $setCookie);
        }
        return $response;
    }

    // ------------------------------------------------------------------
    // Additional API (used by WebUser and AuthMiddleware)
    // ------------------------------------------------------------------

    /**
     * Read a single cookie value from a request.
     */
    public function getRequestCookie(ServerRequestInterface $request, string $name): ?string
    {
        $cookie = FigRequestCookies::get($request, $name);
        return $cookie->getValue();
    }

    /**
     * Queue a generic response cookie.
     */
    public function addResponseCookie(
        string $name,
        string $value,
        ?int $maxAge = null,
        ?string $domain = null,
    ): void {
        $this->responseCookies[$name] = $this->buildSetCookie($name, $value, $maxAge, $domain);
    }

    /**
     * Queue the access_token response cookie.
     */
    public function addResponseAccessTokenCookie(
        string $value,
        ?int $maxAge = null,
        ?string $domain = null,
    ): void {
        $this->addResponseCookie(AccessManagerInterface::COOKIE_TOKEN, $value, $maxAge, $domain);
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private function buildSetCookie(
        string $name,
        string $value,
        ?int $maxAge = null,
        ?string $domain = null,
    ): SetCookie {
        $lifetime     = $this->config->getTokenLifetime();
        $maxAge       ??= $lifetime;
        $expire       = time() + $maxAge;
        $cookieDomain = $domain ?? $this->config->getCookieDomain();
        $secure       = $this->config->isHttpSecure();
        $httpOnly     = $this->config->isCookieHttpOnly();
        $sameSite     = $this->config->getCookieSameSite();

        $cookie = SetCookie::create($name)
            ->withValue($value)
            ->withMaxAge($maxAge)
            ->withExpires(new DateTime('@' . $expire))
            ->withPath('/')
            ->withSecure($secure)
            ->withHttpOnly($httpOnly)
            ->withSameSite(SameSite::fromString($sameSite));

        if ($cookieDomain !== '') {
            $cookie = $cookie->withDomain($cookieDomain);
        }

        return $cookie;
    }
}

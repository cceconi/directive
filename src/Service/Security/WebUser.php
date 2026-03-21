<?php

declare(strict_types=1);

namespace Directive\Service\Security;

use Directive\Service\Security\Authentication\AuthInterface;
use Directive\Service\Security\Role\AbstractRole;
use Directive\Service\Security\Role\GuestRole;
use Psr\Container\ContainerInterface;

/**
 * Abstract base for the authenticated user model.
 *
 * Concrete subclasses implement:
 *   - localAuthenticate(login, pwd): bool
 *   - initializeData(userId): void  — load user fields from your data source
 */
abstract class WebUser implements WebUserInterface
{
    protected string       $userId   = '';
    protected string       $fullName = '';
    protected AbstractRole $role;
    protected bool         $renewPwd = false;

    /** @var array{jwt: string, renewAfter: int}|null */
    protected ?array  $token  = null;
    protected ?string $csrf   = null;
    protected int     $expire = 0;

    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
        $this->role = new GuestRole();
    }

    // ------------------------------------------------------------------
    // WebUserInterface
    // ------------------------------------------------------------------

    public function getRole(): AbstractRole
    {
        return $this->role;
    }

    /**
     * @deprecated Use getRole()->slug() instead.
     */
    public function getProfile(): string
    {
        return $this->getRole()->slug();
    }

    public function isAuthenticated(): bool
    {
        return !$this->isGuest();
    }

    public function isGuest(): bool
    {
        return $this->userId === '';
    }

    public function getId(): string
    {
        return $this->userId;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    /** @return array<string, mixed> */
    public function getAuthenticatedData(): array
    {
        return [
            'email'    => $this->getId(),
            'fullname' => $this->getFullName(),
            'role'     => $this->getRole()->slug(),
            'renewpwd' => $this->renewPwd,
            'csrf'     => $this->csrf,
            'expire'   => $this->expire,
        ];
    }

    /** @return array<string, mixed> */
    public function getAnonymousData(): array
    {
        return [
            'email'    => null,
            'fullname' => null,
            'profile'  => null,
            'renewpwd' => false,
            'csrf'     => null,
            'expire'   => null,
        ];
    }

    // ------------------------------------------------------------------
    // Authentication flow
    // ------------------------------------------------------------------

    public function authenticate(string $login, string $pwd): bool
    {
        if ($this->localAuthenticate($login, $pwd)) {
            $this->initializeData($login);
            $this->initializeSecurityData();
            return true;
        }
        return false;
    }

    /**
     * Load user state from JWT application claims.
     *
     * @param mixed $claims  Array of claim key-value pairs from TokenManager.
     */
    public function loadFromClaims(mixed $claims): void
    {
        /** @var array<string, mixed> $claims */
        $userId = (string) ($claims['client.id'] ?? '');
        if ($userId !== '') {
            $this->initializeData($userId);
        }

        $roleSlug = (string) ($claims['role'] ?? '');
        if ($roleSlug !== '') {
            $this->role = $this->resolveRole($roleSlug);
        }
    }

    public function renewSecurityData(): void
    {
        $this->initializeSecurityData();
    }

    public function expireSecurityData(): void
    {
        /** @var CookiesManagerInterface $cookies */
        $cookies = $this->container->get(CookiesManagerInterface::class);
        $cookies->addResponseAccessTokenCookie('notoken', -500);
        $this->token  = ['jwt' => '', 'renewAfter' => -500];
        $this->csrf   = null;
        $this->expire = -500;
    }

    // ------------------------------------------------------------------
    // Hooks for concrete subclasses
    // ------------------------------------------------------------------

    abstract public function localAuthenticate(string $login, string $pwd): bool;

    abstract protected function initializeData(string $userId): void;

    /**
     * Resolve a role slug to a concrete AbstractRole instance.
     *
     * Override in your application to map slugs to your domain roles.
     * The default implementation always returns a GuestRole.
     */
    protected function resolveRole(string $slug): AbstractRole
    {
        return new GuestRole();
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    protected function initializeSecurityData(): void
    {
        if ($this->renewPwd) {
            $this->expireSecurityData();
        }

        /** @var AuthInterface $auth */
        $auth      = $this->container->get(AuthInterface::class);
        $csrfToken = bin2hex(random_bytes(16));

        $this->token = $auth->generateToken('auth token', [
            'client.id'  => $this->getId(),
            'xsrfToken'  => $csrfToken,
            'role'       => $this->role->slug(),
        ]);

        /** @var CookiesManagerInterface $cookies */
        $cookies = $this->container->get(CookiesManagerInterface::class);
        $cookies->addResponseAccessTokenCookie($this->token['jwt']);

        $this->csrf   = $csrfToken;
        $this->expire = $this->token['renewAfter'];
    }
}

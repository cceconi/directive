<?php

declare(strict_types=1);

namespace Directive\Service\Security\Access\Type;

use Directive\Service\Security\Authentication\AuthInterface;
use Directive\Service\Security\WebUserInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Base for the three access strategies: user (cookie), machine (Bearer), none.
 */
abstract class AbstractAccess
{
    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly ServerRequestInterface $request,
    ) {}

    abstract public function authenticate(): bool;

    // ------------------------------------------------------------------
    // Shared helpers
    // ------------------------------------------------------------------

    protected function checkToken(string $rawToken): bool
    {
        /** @var AuthInterface $auth */
        $auth = $this->container->get(AuthInterface::class);

        if (!$auth->verifyToken($rawToken)) {
            return false;
        }

        if (!$auth->validateToken($rawToken)) {
            return false;
        }

        $claims = $auth->getTokenClaims($rawToken);

        /** @var WebUserInterface $webUser */
        $webUser = $this->container->get(WebUserInterface::class);
        $webUser->loadFromClaims($claims);

        return true;
    }
}

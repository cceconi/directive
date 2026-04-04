<?php

declare(strict_types=1);

namespace Directive\Application\Repository;

/**
 * Marker interface for all domain repositories.
 *
 * Declare no methods — the contract is defined by each concrete
 * application interface (e.g. UserRepositoryInterface).
 *
 * Usage:
 *   interface UserRepositoryInterface extends RepositoryInterface { ... }
 *
 * This marker allows the DI container and PHPStan to distinguish
 * repository bindings from other services.
 */
interface RepositoryInterface {}

<?php

declare(strict_types=1);

namespace Directive\Exception;

/**
 * Thrown when a persistence operation fails at the infrastructure level.
 *
 * This exception covers driver-level failures: connection lost, timeout,
 * network error, etc. It is NOT meant for domain-level errors such as a
 * missing entity — use Directive\Application\Exception\EntityNotFoundException
 * for those.
 *
 * PersistenceException is infrastructure-scoped and lives in Directive\Exception\
 * (alongside ConfigurationException, SecurityException…), not in Http\Exception\.
 */
class PersistenceException extends DirectiveException {}

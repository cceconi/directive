<?php

declare(strict_types=1);

namespace Directive\Service\Security\Authentication;

use Directive\Service\Configuration\ConfigurationInterface;
use Directive\Service\Logging\WebLoggerInterface;

/**
 * Base for all authentication strategies.
 */
abstract class AbstractAuth implements AuthInterface
{
    public function __construct(
        protected readonly ConfigurationInterface $config,
        protected readonly WebLoggerInterface $logger,
    ) {}
}

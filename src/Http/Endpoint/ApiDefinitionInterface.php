<?php

declare(strict_types=1);

namespace Directive\Http\Endpoint;

use Directive\Http\Routing\Domain;
use Psr\Container\ContainerInterface;

/**
 * Contract for user-defined API declaration classes.
 *
 * Each implementation calls ApiTree::domain() and builds the full tree,
 * then returns the root Domain.
 *
 * Example:
 *   class MyApi implements ApiDefinitionInterface {
 *       public function createApi(ContainerInterface $container): Domain {
 *           return ApiTree::domain('users')
 *               ->version('v1')
 *                   ->service(...);
 *       }
 *   }
 */
interface ApiDefinitionInterface
{
    public function createApi(ContainerInterface $container): Domain;
}

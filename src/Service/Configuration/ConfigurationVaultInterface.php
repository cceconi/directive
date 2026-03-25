<?php

declare(strict_types=1);

namespace Directive\Service\Configuration;

/**
 * Read-only contract for a secrets vault.
 *
 * Implement this interface to expose secrets (API keys, passwords, …) to
 * the ConfigVerifyCommand and any other infrastructure that needs to
 * enumerate available secret keys without reading their values.
 */
interface ConfigurationVaultInterface
{
    /**
     * Return the list of secret keys managed by this vault.
     *
     * @return list<string>
     */
    public function getKeys(): array;
}

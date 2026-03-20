<?php

declare(strict_types=1);

namespace Directive\Service\Maintenance;

/** Reads and exposes the current maintenance-mode state. */
interface MaintenanceManagerInterface
{
    public function isActive(): bool;

    public function getMessage(): string;

    /** Estimated downtime period, e.g. "2h" or "30min". */
    public function getPeriod(): string;
}

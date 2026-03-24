<?php

declare(strict_types=1);

namespace Directive\Http\Routing;

/**
 * Lifecycle status of an API version.
 */
enum VersionStatus: string
{
    /** Fully available. */
    case Open = 'open';

    /** Still functional but scheduled for removal. Clients should migrate. */
    case Deprecated = 'deprecated';

    /** Permanently removed. Returns 410 Gone. */
    case Closed = 'closed';

    /** Work in progress. Not publicly available. Returns 410 Gone. */
    case Wip = 'wip';
}

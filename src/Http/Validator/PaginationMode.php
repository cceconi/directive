<?php

declare(strict_types=1);

namespace Directive\Http\Validator;

/**
 * Pagination strategy for QueryParametersValidator.
 *
 * Modes are ordered by recommendation (best to least):
 */
enum PaginationMode: string
{
    /**
     * Recommended — page[after] and/or page[before] keyed to an indexed sorted column.
     *
     * Performance: O(log n) index scan — the DBMS jumps directly to the anchor key.
     * Coherence: strong — a concurrent insert does not cause duplicates or missing rows
     * because the anchor key remains stable across pages.
     *
     * Use for any SQL collection sorted by an indexed column (id, created_at, …).
     */
    case Keyset = 'keyset';

    /**
     * For opaque sources — page[cursor] is forwarded verbatim to the underlying source.
     *
     * Use for third-party APIs or engines (Elasticsearch, DynamoDB, …) that expose
     * their own pagination pointer.
     */
    case Cursor = 'cursor';

    /**
     * Restricted use — page[number] + page[size].
     *
     * Performance: O(n) table scan — the DBMS scans OFFSET rows to return LIMIT results.
     * Performance degrades linearly with page depth. Coherence is fragile: a concurrent
     * insert can cause a duplicate or a missing row when navigating to the next page.
     *
     * Reserve for small, stable collections (< a few thousand entries) or when page-number
     * navigation is an explicit functional requirement.
     */
    case Offset = 'offset';
}

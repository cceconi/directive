<?php

declare(strict_types=1);

namespace Directive\Http\Validator;

use Directive\Http\Input\Constraint\GenericConstraintInterface;
use Directive\Http\Input\SimpleString;
use Directive\Http\Request\RequestEntity;

/**
 * Abstract base validator for collection endpoints with bracket-notation query parameters.
 *
 * Subclasses declare allowed filters, sort fields and pagination mode inside
 * registerQueryParameters() using the three helpers: allowFilter(), allowSort(), pagination().
 *
 * Bracket-notation convention:
 *   filter[field]=value   → accessible as "filter.field" in RequestEntity
 *   sort[field]=asc|desc  → accessible as "sort.field" in RequestEntity
 *   page[after]=<key>     → accessible as "page.after" in RequestEntity  (keyset)
 *   page[cursor]=<opaque> → accessible as "page.cursor" in RequestEntity (cursor)
 *   page[number]=1        → accessible as "page.number" in RequestEntity (offset)
 *   page[size]=20         → accessible as "page.size" in RequestEntity   (offset)
 *
 * OpenAPI schema is auto-generated via getOpenApiParameters() — called by OpenApiCommand.
 */
abstract class QueryParametersValidator extends AbstractRequestValidator
{
    private ?PaginationMode $paginationMode = null;

    /** @var array<string, list<GenericConstraintInterface>> */
    private array $allowedFilters = [];

    /** @var list<string> */
    private array $allowedSortFields = [];

    /** @var array<string, SimpleString> */
    private array $validatedQueryValues = [];

    // ------------------------------------------------------------------
    // AbstractRequestValidator — final override of the register() hook
    // ------------------------------------------------------------------

    /**
     * Framework entry-point: resets state, calls registerQueryParameters(),
     * then validates query string against declarations.
     *
     * Subclasses MUST implement registerQueryParameters() instead of register().
     */
    final protected function register(): void
    {
        $this->paginationMode        = null;
        $this->allowedFilters        = [];
        $this->allowedSortFields     = [];
        $this->validatedQueryValues  = [];

        $this->registerQueryParameters();

        /** @var array<string, mixed> $query */
        $query = $this->getRequest()->getQueryParams();

        $this->processFilters($query);
        $this->processSorts($query);
        $this->processPage($query);
    }

    // ------------------------------------------------------------------
    // Hook for subclasses
    // ------------------------------------------------------------------

    /**
     * Declare allowed filters, sort fields and pagination mode.
     * Call allowFilter(), allowSort() and pagination() from here.
     */
    abstract protected function registerQueryParameters(): void;

    // ------------------------------------------------------------------
    // Declaration helpers (call from registerQueryParameters())
    // ------------------------------------------------------------------

    /**
     * Allow a filter parameter: filter[field]=value.
     *
     * Constraints are ANDed: all must pass for the value to be accepted.
     */
    final protected function allowFilter(string $field, GenericConstraintInterface ...$constraints): void
    {
        $this->allowedFilters[$field] = array_values($constraints);
    }

    /**
     * Allow sort parameters: sort[field]=asc|desc.
     */
    final protected function allowSort(string ...$fields): void
    {
        foreach ($fields as $field) {
            $this->allowedSortFields[] = $field;
        }
    }

    /**
     * Set the pagination strategy. Only one mode per validator.
     */
    final protected function pagination(PaginationMode $mode): void
    {
        $this->paginationMode = $mode;
    }

    // ------------------------------------------------------------------
    // OpenAPI schema generation
    // ------------------------------------------------------------------

    /**
     * Returns the OpenAPI 3.1 parameters block for this validator's declared fields.
     * Called by OpenApiCommand — safe to call without a request set.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOpenApiParameters(): array
    {
        // Reset and re-collect declarations (no request needed here)
        $this->paginationMode    = null;
        $this->allowedFilters    = [];
        $this->allowedSortFields = [];

        $this->registerQueryParameters();

        /** @var array<int, array<string, mixed>> $params */
        $params = [];

        foreach (array_keys($this->allowedFilters) as $field) {
            $params[] = [
                'name'     => "filter[$field]",
                'in'       => 'query',
                'required' => false,
                'schema'   => ['type' => 'string'],
            ];
        }

        foreach ($this->allowedSortFields as $field) {
            $params[] = [
                'name'     => "sort[$field]",
                'in'       => 'query',
                'required' => false,
                'schema'   => ['type' => 'string', 'enum' => ['asc', 'desc']],
            ];
        }

        if ($this->paginationMode !== null) {
            foreach ($this->buildPaginationParameters($this->paginationMode) as $param) {
                $params[] = $param;
            }
        }

        return $params;
    }

    // ------------------------------------------------------------------
    // RequestEntity creation — inject validated query values
    // ------------------------------------------------------------------

    /**
     * Creates the RequestEntity and pre-populates it with validated query fields.
     * The parent then populates it further with validated body fields (if any).
     */
    protected function createRequestEntity(): RequestEntity
    {
        $entity = parent::createRequestEntity();

        foreach ($this->validatedQueryValues as $name => $field) {
            $entity->registerField($name, $field);
        }

        return $entity;
    }

    // ------------------------------------------------------------------
    // Internal: query string processing
    // ------------------------------------------------------------------

    /**
     * @param array<string, mixed> $query
     */
    private function processFilters(array $query): void
    {
        /** @var mixed $rawFilters */
        $rawFilters = $query['filter'] ?? null;

        if ($rawFilters === null) {
            return;
        }

        if (!is_array($rawFilters)) {
            $this->addError('filter', 'Filter parameter must use bracket notation: filter[field]=value.');

            return;
        }

        /** @var mixed $value */
        foreach ($rawFilters as $field => $value) {
            if (!is_string($field)) {
                continue;
            }

            if (!array_key_exists($field, $this->allowedFilters)) {
                $this->addError(
                    "filter[$field]",
                    sprintf('Filter "%s" is not allowed.', $field),
                );
                continue;
            }

            $input = new SimpleString();
            $input->hydrate($value);
            $cleaned = $input->getCleanedValue();

            $valid = true;
            foreach ($this->allowedFilters[$field] as $constraint) {
                if (!$constraint->checkConstraint($cleaned)) {
                    $valid = false;
                    break;
                }
            }

            if (!$valid) {
                $this->addError(
                    "filter[$field]",
                    sprintf('Filter "%s" has an invalid value.', $field),
                );
            } else {
                $this->validatedQueryValues["filter.$field"] = $input;
            }
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    private function processSorts(array $query): void
    {
        /** @var mixed $rawSorts */
        $rawSorts = $query['sort'] ?? null;

        if ($rawSorts === null) {
            return;
        }

        if (!is_array($rawSorts)) {
            $this->addError('sort', 'Sort parameter must use bracket notation: sort[field]=asc|desc.');

            return;
        }

        /** @var mixed $value */
        foreach ($rawSorts as $field => $value) {
            if (!is_string($field)) {
                continue;
            }

            if (!in_array($field, $this->allowedSortFields, true)) {
                $this->addError(
                    "sort[$field]",
                    sprintf('Sort field "%s" is not allowed.', $field),
                );
                continue;
            }

            if (!is_string($value) || !in_array($value, ['asc', 'desc'], true)) {
                $this->addError(
                    "sort[$field]",
                    sprintf('Sort field "%s" must be "asc" or "desc".', $field),
                );
            } else {
                $input = new SimpleString();
                $input->hydrate($value);
                $this->validatedQueryValues["sort.$field"] = $input;
            }
        }
    }

    /**
     * @param array<string, mixed> $query
     */
    private function processPage(array $query): void
    {
        /** @var mixed $rawPage */
        $rawPage = $query['page'] ?? null;

        if ($rawPage === null) {
            // No page params — valid in all modes (means "first page")
            return;
        }

        if (!is_array($rawPage)) {
            $this->addError('page', 'Page parameter must use bracket notation: page[key]=value.');

            return;
        }

        if ($this->paginationMode === null) {
            foreach (array_keys($rawPage) as $key) {
                $this->addError(
                    "page[$key]",
                    'Pagination is not declared on this endpoint.',
                );
            }

            return;
        }

        $allowedKeys = $this->allowedPageKeys($this->paginationMode);

        /** @var mixed $value */
        foreach ($rawPage as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (!in_array($key, $allowedKeys, true)) {
                $this->addError(
                    "page[$key]",
                    sprintf(
                        'Page key "%s" is not valid for pagination mode "%s".',
                        $key,
                        $this->paginationMode->value,
                    ),
                );
                continue;
            }

            // Offset mode: number and size must be positive integers
            if ($this->paginationMode === PaginationMode::Offset) {
                if (!is_string($value) || !ctype_digit($value) || (int) $value < 1) {
                    $this->addError(
                        "page[$key]",
                        sprintf('Page "%s" must be a positive integer (≥ 1).', $key),
                    );
                    continue;
                }
            } elseif (!is_string($value) || $value === '') {
                $this->addError(
                    "page[$key]",
                    sprintf('Page "%s" must be a non-empty string.', $key),
                );
                continue;
            }

            $input = new SimpleString();
            $input->hydrate($value);
            $this->validatedQueryValues["page.$key"] = $input;
        }

        // Offset cross-validation: number and size must be provided together
        if ($this->paginationMode === PaginationMode::Offset && !$this->hasErrors()) {
            $hasNumber = isset($this->validatedQueryValues['page.number']);
            $hasSize   = isset($this->validatedQueryValues['page.size']);

            if ($hasNumber && !$hasSize) {
                $this->addError('page[size]', 'page[size] is required when page[number] is provided.');
            } elseif ($hasSize && !$hasNumber) {
                $this->addError('page[number]', 'page[number] is required when page[size] is provided.');
            }
        }
    }

    /**
     * @return list<string>
     */
    private function allowedPageKeys(PaginationMode $mode): array
    {
        return match ($mode) {
            PaginationMode::Keyset => ['after', 'before'],
            PaginationMode::Cursor => ['cursor'],
            PaginationMode::Offset => ['number', 'size'],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPaginationParameters(PaginationMode $mode): array
    {
        return match ($mode) {
            PaginationMode::Keyset => [
                ['name' => 'page[after]',  'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                ['name' => 'page[before]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
            ],
            PaginationMode::Cursor => [
                ['name' => 'page[cursor]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
            ],
            PaginationMode::Offset => [
                ['name' => 'page[number]', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1]],
                ['name' => 'page[size]',   'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'minimum' => 1]],
            ],
        };
    }
}

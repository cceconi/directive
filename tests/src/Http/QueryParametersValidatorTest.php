<?php

declare(strict_types=1);

use Directive\Http\Input\Constraint\GenericConstraintInterface;
use Directive\Http\Validator\PaginationMode;
use Directive\Http\Validator\QueryParametersValidator;
use Nyholm\Psr7\ServerRequest;

// ------------------------------------------------------------------
// Helpers
// ------------------------------------------------------------------

/** @param array<string, mixed> $queryParams */
function makeQueryRequest(array $queryParams = []): ServerRequest
{
    $req = new ServerRequest('GET', '/test');

    return $queryParams !== [] ? $req->withQueryParams($queryParams) : $req;
}

// ------------------------------------------------------------------
// 5.1 — Filters
// ------------------------------------------------------------------

describe('QueryParametersValidator — filters', function () {
    it('accepts a declared filter with a valid value', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
            }
        };

        $validator->setRequest(makeQueryRequest(['filter' => ['name' => 'foo']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('filter.name'))->toBe('foo');
    });

    it('rejects an undeclared filter', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
            }
        };

        $validator->setRequest(makeQueryRequest(['filter' => ['email' => 'test@example.com']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('filter[email]');
    });

    it('accepts a declared filter with a passing constraint', function () {
        $minLength = new class implements GenericConstraintInterface {
            public function checkConstraint(mixed $value): bool { return strlen((string) $value) >= 2; }
            public function checkType(mixed $value): bool { return true; }
            public function checkValue(mixed $value): bool { return strlen((string) $value) >= 2; }
        };

        $validator = new class ($minLength) extends QueryParametersValidator {
            public function __construct(private readonly GenericConstraintInterface $c) {}

            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name', $this->c);
            }
        };

        $validator->setRequest(makeQueryRequest(['filter' => ['name' => 'ab']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
    });

    it('accepts no filter params when none are sent', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
            }
        };

        $validator->setRequest(makeQueryRequest([]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
    });

    it('rejects filter when not using bracket notation', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
            }
        };

        $validator->setRequest(makeQueryRequest(['filter' => 'name=foo']));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('filter');
    });
});

// ------------------------------------------------------------------
// Sorts
// ------------------------------------------------------------------

describe('QueryParametersValidator — sorts', function () {
    it('accepts a declared sort field with asc', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowSort('name', 'createdAt');
            }
        };

        $validator->setRequest(makeQueryRequest(['sort' => ['name' => 'desc']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('sort.name'))->toBe('desc');
    });

    it('rejects an undeclared sort field', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowSort('name');
            }
        };

        $validator->setRequest(makeQueryRequest(['sort' => ['email' => 'asc']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('sort[email]');
    });

    it('rejects an invalid sort value', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowSort('name');
            }
        };

        $validator->setRequest(makeQueryRequest(['sort' => ['name' => 'random']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('sort[name]');
    });
});

// ------------------------------------------------------------------
// 5.2 — Pagination Keyset
// ------------------------------------------------------------------

describe('QueryParametersValidator — pagination Keyset', function () {
    it('accepts page[after]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['after' => '42']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('page.after'))->toBe('42');
    });

    it('accepts page[before]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['before' => '100']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('page.before'))->toBe('100');
    });

    it('accepts page[after] and page[before] simultaneously', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['after' => '10', 'before' => '50']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('page.after'))->toBe('10');
        expect($entity->get('page.before'))->toBe('50');
    });

    it('accepts no page params (first page)', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $validator->setRequest(makeQueryRequest([]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
    });

    it('rejects a key from another mode', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['cursor' => 'abc']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[cursor]');
    });
});

// ------------------------------------------------------------------
// 5.3 — Pagination Cursor
// ------------------------------------------------------------------

describe('QueryParametersValidator — pagination Cursor', function () {
    it('accepts page[cursor]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Cursor);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['cursor' => 'eyJpZCI6NDJ9']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('page.cursor'))->toBe('eyJpZCI6NDJ9');
    });

    it('rejects a key from another mode', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Cursor);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['after' => '42']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[after]');
    });

    it('rejects page[number] in cursor mode', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Cursor);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => '1']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
    });
});

// ------------------------------------------------------------------
// 5.4 — Pagination Offset
// ------------------------------------------------------------------

describe('QueryParametersValidator — pagination Offset', function () {
    it('accepts page[number] and page[size]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => '2', 'size' => '20']]));
        $entity = $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeFalse();
        expect($entity->get('page.number'))->toBe('2');
        expect($entity->get('page.size'))->toBe('20');
    });

    it('rejects page[number] with a non-integer value', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => 'abc']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[number]');
    });

    it('rejects page[number] = 0 (must be >= 1)', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => '0']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
    });

    it('rejects a key from another mode', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['after' => '42']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[after]');
    });

    it('rejects any page key when no pagination is declared', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                // no pagination declared
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => '1']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[number]');
    });

    it('rejects page[number] alone without page[size]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['number' => '2']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[size]');
    });

    it('rejects page[size] alone without page[number]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $validator->setRequest(makeQueryRequest(['page' => ['size' => '20']]));
        $validator->getRequestEntity();

        expect($validator->hasErrors())->toBeTrue();
        expect($validator->getErrors()[0]['property'])->toBe('page[number]');
    });
});

// ------------------------------------------------------------------
// 5.5 — getOpenApiParameters()
// ------------------------------------------------------------------

describe('QueryParametersValidator — getOpenApiParameters()', function () {
    it('keyset generates page[after] and page[before] as strings', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
                $this->allowSort('name', 'createdAt');
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $params = $validator->getOpenApiParameters();
        $names  = array_column($params, 'name');

        expect($names)->toContain('filter[name]');
        expect($names)->toContain('sort[name]');
        expect($names)->toContain('sort[createdAt]');
        expect($names)->toContain('page[after]');
        expect($names)->toContain('page[before]');
        expect($names)->not->toContain('page[cursor]');
        expect($names)->not->toContain('page[number]');

        $afterParam = $params[array_search('page[after]', $names, true)];
        expect($afterParam['schema']['type'])->toBe('string');
        expect($afterParam['required'])->toBeFalse();
    });

    it('offset generates page[number] and page[size] with minimum: 1', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Offset);
            }
        };

        $params = $validator->getOpenApiParameters();
        $names  = array_column($params, 'name');

        expect($names)->toContain('page[number]');
        expect($names)->toContain('page[size]');

        $numberParam = $params[array_search('page[number]', $names, true)];
        expect($numberParam['schema']['type'])->toBe('integer');
        expect($numberParam['schema']['minimum'])->toBe(1);
    });

    it('cursor generates page[cursor] as string', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->pagination(PaginationMode::Cursor);
            }
        };

        $params = $validator->getOpenApiParameters();
        $names  = array_column($params, 'name');

        expect($names)->toContain('page[cursor]');
        expect($names)->not->toContain('page[after]');
        expect($names)->not->toContain('page[number]');
    });

    it('all parameters are required: false', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowFilter('name');
                $this->allowSort('name');
                $this->pagination(PaginationMode::Keyset);
            }
        };

        $params = $validator->getOpenApiParameters();

        foreach ($params as $param) {
            expect($param['required'])->toBeFalse();
        }
    });

    it('sort parameters have enum [asc, desc]', function () {
        $validator = new class extends QueryParametersValidator {
            protected function registerQueryParameters(): void
            {
                $this->allowSort('name');
            }
        };

        $params = $validator->getOpenApiParameters();
        $sort   = $params[0];

        expect($sort['name'])->toBe('sort[name]');
        expect($sort['schema']['enum'])->toBe(['asc', 'desc']);
    });
});

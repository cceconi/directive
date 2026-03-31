<?php

declare(strict_types=1);

use Directive\Application\Command\AbstractCommand;
use Directive\Application\Query\AbstractQuery;

// ---------------------------------------------------------------------------
// Concrete stubs
// ---------------------------------------------------------------------------

class StubCommand extends AbstractCommand {}
class StubQuery extends AbstractQuery {}

// ---------------------------------------------------------------------------
// AbstractCommand
// ---------------------------------------------------------------------------

describe('AbstractCommand', function () {
    it('generates a non-empty ID on construction', function () {
        $cmd = new StubCommand();
        expect($cmd->getId())->not->toBeEmpty();
        expect(strlen($cmd->getId()))->toBe(36);
    });

    it('provides a createdAt DateTimeImmutable', function () {
        $cmd = new StubCommand();
        expect($cmd->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('two commands have distinct IDs', function () {
        $c1 = new StubCommand();
        $c2 = new StubCommand();
        expect($c1->getId())->not->toBe($c2->getId());
    });
});

// ---------------------------------------------------------------------------
// AbstractQuery
// ---------------------------------------------------------------------------

describe('AbstractQuery', function () {
    it('generates a non-empty ID on construction', function () {
        $q = new StubQuery();
        expect($q->getId())->not->toBeEmpty();
        expect(strlen($q->getId()))->toBe(36);
    });

    it('provides a createdAt DateTimeImmutable', function () {
        $q = new StubQuery();
        expect($q->getCreatedAt())->toBeInstanceOf(\DateTimeImmutable::class);
    });

    it('two queries have distinct IDs', function () {
        $q1 = new StubQuery();
        $q2 = new StubQuery();
        expect($q1->getId())->not->toBe($q2->getId());
    });
});

// ---------------------------------------------------------------------------
// S1 — Type distinctness (CQRS D1)
// ---------------------------------------------------------------------------

describe('AbstractCommand / AbstractQuery type distinctness', function () {
    it('a Command is not an AbstractQuery', function () {
        $cmd = new StubCommand();
        expect($cmd)->not->toBeInstanceOf(\Directive\Application\Query\AbstractQuery::class);
    });

    it('a Query is not an AbstractCommand', function () {
        $q = new StubQuery();
        expect($q)->not->toBeInstanceOf(\Directive\Application\Command\AbstractCommand::class);
    });
});

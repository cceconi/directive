<?php

declare(strict_types=1);

use Directive\Testing\InMemoryCache;

describe('InMemoryCache', function (): void {

    beforeEach(function (): void {
        $this->cache = new InMemoryCache();
    });

    it('set() and get() roundtrip', function (): void {
        $this->cache->set('foo', 'bar');
        expect($this->cache->get('foo'))->toBe('bar');
    });

    it('get() returns default when key is absent', function (): void {
        expect($this->cache->get('missing', 'default'))->toBe('default');
    });

    it('get() returns null default when key is absent and no default given', function (): void {
        expect($this->cache->get('missing'))->toBeNull();
    });

    it('has() returns true for existing key', function (): void {
        $this->cache->set('key', 'value');
        expect($this->cache->has('key'))->toBeTrue();
    });

    it('has() returns false for missing key', function (): void {
        expect($this->cache->has('absent'))->toBeFalse();
    });

    it('get() returns default for expired TTL (int)', function (): void {
        $this->cache->set('ttl-key', 'data', -1); // already expired
        expect($this->cache->get('ttl-key', 'gone'))->toBe('gone');
    });

    it('has() returns false for expired entry', function (): void {
        $this->cache->set('ttl-key', 'data', -1);
        expect($this->cache->has('ttl-key'))->toBeFalse();
    });

    it('delete() removes a key', function (): void {
        $this->cache->set('key', 'val');
        $this->cache->delete('key');
        expect($this->cache->has('key'))->toBeFalse();
    });

    it('clear() removes all entries', function (): void {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->clear();
        expect($this->cache->has('a'))->toBeFalse();
        expect($this->cache->has('b'))->toBeFalse();
    });

    it('setMultiple() and getMultiple() roundtrip', function (): void {
        $this->cache->setMultiple(['x' => 10, 'y' => 20]);
        $result = $this->cache->getMultiple(['x', 'y', 'z'], 0);
        expect(iterator_to_array($result))->toBe(['x' => 10, 'y' => 20, 'z' => 0]);
    });

    it('deleteMultiple() removes multiple keys', function (): void {
        $this->cache->setMultiple(['a' => 1, 'b' => 2, 'c' => 3]);
        $this->cache->deleteMultiple(['a', 'b']);
        expect($this->cache->has('a'))->toBeFalse();
        expect($this->cache->has('b'))->toBeFalse();
        expect($this->cache->has('c'))->toBeTrue();
    });
});

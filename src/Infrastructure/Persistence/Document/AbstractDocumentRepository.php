<?php

declare(strict_types=1);

namespace Directive\Infrastructure\Persistence\Document;

use Directive\Exception\PersistenceException;

/**
 * Base class for document-store repositories using a framework-agnostic ClientInterface.
 *
 * The application provides a concrete adapter (e.g. MongoClientAdapter) that
 * implements ClientInterface and wraps the SDK of its choice. This class only
 * handles injection, exception wrapping, and pattern documentation.
 *
 * -------------------------------------------------------------------------
 * Multi-document atomicity
 * -------------------------------------------------------------------------
 * This framework does not expose a transact() method on document repositories.
 * Multi-document atomicity (e.g. MongoDB ClientSession, DynamoDB transactions)
 * is a responsibility of the application adapter — not the framework — because
 * the underlying APIs differ significantly across document stores.
 *
 * For cross-aggregate consistency, prefer publishing domain events and handling
 * them asynchronously rather than relying on distributed transactions.
 * -------------------------------------------------------------------------
 *
 * Usage:
 *   final class ProductRepository extends AbstractDocumentRepository
 *       implements ProductRepositoryInterface
 *   {
 *       public function findBySku(string $sku): ?Product
 *       {
 *           $doc = $this->findOne('products', ['sku' => $sku]);
 *           return $doc !== null ? Product::fromDocument($doc) : null;
 *       }
 *   }
 */
abstract class AbstractDocumentRepository
{
    public function __construct(
        protected readonly ClientInterface $client,
    ) {}

    /**
     * @param array<string, mixed> $filter
     * @return array<string, mixed>|null
     * @throws PersistenceException
     */
    protected function findOne(string $collection, array $filter): ?array
    {
        try {
            return $this->client->findOne($collection, $filter);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     * @throws PersistenceException
     */
    protected function findMany(string $collection, array $filter, array $options = []): array
    {
        try {
            return $this->client->findMany($collection, $filter, $options);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $data
     * @throws PersistenceException
     */
    protected function upsert(string $collection, array $filter, array $data): void
    {
        try {
            $this->client->upsert($collection, $filter, $data);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $filter
     * @throws PersistenceException
     */
    protected function remove(string $collection, array $filter): void
    {
        try {
            $this->client->remove($collection, $filter);
        } catch (\Throwable $e) {
            throw new PersistenceException($e->getMessage(), 0, $e);
        }
    }
}

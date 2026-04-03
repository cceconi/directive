<?php

declare(strict_types=1);

namespace {{namespace}}\Spi;

use {{namespace}}\Model\{{name}};
use {{namespace}}\Model\{{name}}Id;

interface {{name}}RepositoryInterface
{
    public function findById({{name}}Id $id): ?{{name}};

    public function save({{name}} $entity): void;

    public function delete({{name}}Id $id): void;

    /**
     * @return array<int, {{name}}>
     */
    public function findAll(): array;
}

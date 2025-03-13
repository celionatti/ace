<?php

declare(strict_types=1);

namespace Ace\Database\Interface;

use JsonSerializable;
use Ace\Database\QueryBuilder\QueryBuilder;

interface ModelInterface extends JsonSerializable
{
    /**
     * Apply search conditions to the query
     *
     * @param QueryBuilder $query
     * @param string $searchTerm
     * @return void
     */
    public function applySearch(QueryBuilder $query, string $searchTerm): void;

    /**
     * Get the table name for the model
     *
     * @return string
     */
    public function getTable(): string;
}
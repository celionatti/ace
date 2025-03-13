<?php

declare(strict_types=1);

namespace Ace\Database\Relationships;

class BelongsTo
{
    protected $query;
    protected $child;
    protected $foreignKey;
    protected $ownerKey;

    public function __construct(QueryBuilder $query, Model $child, string $foreignKey, string $ownerKey) {
        $this->query = $query->where($ownerKey, '=', $child->{$foreignKey});
        $this->child = $child;
        $this->foreignKey = $foreignKey;
        $this->ownerKey = $ownerKey;
    }

    public function first(): ?Model {
        return $this->query->first();
    }

    public function getResults()
    {
        return $this->query->first();
    }
}
<?php

declare(strict_types=1);

namespace Ace\Database\Relationships;

class HasMany
{
    protected $query;
    protected $parent;
    protected $foreignKey;
    protected $localKey;

    public function __construct(QueryBuilder $query, Model $parent, string $foreignKey, string $localKey)
    {
        $this->query = $query->where($foreignKey, '=', $parent->{$localKey});
        $this->parent = $parent;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public function get(): Collection
    {
        return $this->query->get();
    }

    public function getResults()
    {
        return $this->query->get();
    }

    public function create(array $attributes)
    {
        $foreignKey = $this->foreignKey;
        $attributes[$foreignKey] = $this->parent->{$this->localKey};
        return $this->related->newQuery()->create($attributes);
    }
}
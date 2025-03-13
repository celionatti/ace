<?php

declare(strict_types=1);

namespace Ace\ace\Database\Relationships\traits;

trait HasRelationships
{
    public function hasMany($related, $foreignKey = null, $localKey = null) {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $localKey = $localKey ?: $this->primaryKey;

        return new HasMany(
            (new $related)->newQuery(),
            $this,
            $foreignKey,
            $localKey
        );
    }

    public function belongsTo($related, $foreignKey = null, $ownerKey = null) {
        $foreignKey = $foreignKey ?: (new $related)->getForeignKey();
        $ownerKey = $ownerKey ?: (new $related)->primaryKey;

        return new BelongsTo(
            (new $related)->newQuery(),
            $this,
            $foreignKey,
            $ownerKey
        );
    }
}
<?php

declare(strict_types=1);

namespace Ace\ace\Database\Model;

abstract class Model
{
    use HasRelationships;

    protected static $connection = 'default';
    protected $table;
    protected $primaryKey = 'id';
    protected $attributes = [];
    protected $original = [];
    protected $exists = false;
    protected $fillable = [];
    protected $guarded = ['id'];

    // Get the foreign key for the model (added this method)
    public function getForeignKey() {
        return strtolower(class_basename($this)) . '_id';
    }

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes)
    {
        foreach ($this->filterFillable($attributes) as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    protected function filterFillable(array $attributes)
    {
        if (!empty($this->fillable)) {
            return array_intersect_key($attributes, array_flip($this->fillable));
        }
        return array_diff_key($attributes, array_flip($this->guarded));
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute($key)
    {
        if (method_exists($this, $key)) {
            return $this->$key()->getResults();
        }
        return $this->attributes[$key] ?? null;
    }

    public function __get($key) {
        return $this->getAttribute($key);
    }

    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }

    public function save()
    {
        if ($this->exists) {
            $this->performUpdate();
        } else {
            $this->performInsert();
        }
        return $this;
    }

    protected function performInsert()
    {
        $attributes = $this->getDirtyAttributes();

        $query = $this->newQuery();
        $result = $query->insert($attributes);

        if ($result) {
            $this->exists = true;
            $this->original = $this->attributes;
            $this->{$this->primaryKey} = $query->getConnection()->lastInsertId();
        }
        return $result;
    }

    protected function performUpdate()
    {
        $dirty = $this->getDirtyAttributes();

        if (empty($dirty)) {
            return true;
        }

        $query = $this->newQuery()
            ->where($this->primaryKey, '=', $this->getKey());

        $result = $query->update($dirty);

        if ($result) {
            $this->original = $this->attributes;
        }
        return $result;
    }

    public function getKey() {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getDirtyAttributes()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public static function query() {
        return (new static)->newQuery();
    }

    protected function newQuery()
    {
        return (new QueryBuilder(Database::connection(static::$connection)))
            ->table($this->getTable())
            ->setModel($this);
    }

    public function getTable() {
        return $this->table ?? strtolower(Str::plural(class_basename($this)));
    }

    public static function find($id) {
        return static::query()->where((new static)->primaryKey, '=', $id)->first();
    }

    public function delete() {
        if (!$this->exists) {
            return false;
        }

        return $this->newQuery()
            ->where($this->primaryKey, '=', $this->getKey())
            ->delete();
    }
}

<?php

namespace Ace;

use PDO;
use Exception;
use Ace\Exceptions\ModelNotFoundException;

class QueryBuilder
{
    protected string $modelClass;
    protected string $table;
    protected array $wheres = [];
    protected array $params = [];
    protected ?string $orderBy = null;
    protected ?int $limit = null;
    protected ?int $offset = null;

    public function __construct(string $modelClass)
    {
        $this->modelClass = $modelClass;
        $this->table = $modelClass::tableName();
    }

    /**
     * Add a basic where clause to the query
     */
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        // Parameter placeholder name
        $paramName = 'p_' . count($this->params) . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $column);
        $this->wheres[] = "`$column` $operator :$paramName";
        $this->params[$paramName] = $value;
        return $this;
    }

    /**
     * Add an order by clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orderBy = "`$column` $direction";
        return $this;
    }

    /**
     * Set query limit
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Set query offset
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Compile and execute select statement, returning array of Model instances
     */
    public function get(): array
    {
        $db = Application::$app->db;
        if (!$db) {
            return [];
        }

        $sql = "SELECT * FROM `{$this->table}`";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if ($this->orderBy) {
            $sql .= " ORDER BY {$this->orderBy}";
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        $statement = $db->prepare($sql);
        foreach ($this->params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        $records = $statement->fetchAll();

        $models = [];
        foreach ($records as $record) {
            $model = new $this->modelClass();
            $model->loadData($record);
            $models[] = $model;
        }

        return $models;
    }

    /**
     * Get first matching record or null
     */
    public function first(): ?Model
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Get the first record matching the query or throw a ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public function firstOrFail(): Model
    {
        $record = $this->first();

        if (!$record) {
            throw new ModelNotFoundException($this->modelClass);
        }

        return $record;
    }

    /**
     * Find a record by primary key, or throw a ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(mixed $id): Model
    {
        $tempModel = new $this->modelClass();
        $primaryKey = $tempModel->primaryKey();

        $record = $this->where($primaryKey, $id)->first();

        if (!$record) {
            throw new ModelNotFoundException($this->modelClass, $id);
        }

        return $record;
    }

    /**
     * Get total count matching query criteria
     */
    public function count(): int
    {
        $db = Application::$app->db;
        if (!$db) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM `{$this->table}`";

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        $statement = $db->prepare($sql);
        foreach ($this->params as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        $return = (int)$statement->fetchColumn();
        return $return;
    }
}


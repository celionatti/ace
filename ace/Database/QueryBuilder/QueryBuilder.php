<?php

declare(strict_types=1);

namespace Ace\ace\Database\QueryBuilder;

use Ace\ace\Database\Interface\DatabaseInterface;

class QueryBuilder
{
    /**
     * @var DatabaseInterface The database connection
     */
    private DatabaseInterface $db;

    /**
     * @var string The table name
     */
    private string $table = '';

    /**
     * @var array Query parts
     */
    private array $parts = [
        'select' => ['*'],
        'where' => [],
        'order' => [],
        'limit' => null,
        'offset' => null,
        'params' => []
    ];

    /**
     * Constructor
     *
     * @param DatabaseInterface $db The database connection
     */
    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Set the table to query
     *
     * @param string $table The table name
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Set the columns to select
     *
     * @param string|array $columns The columns to select
     * @return self
     */
    public function select(string|array $columns): self
    {
        $this->parts['select'] = is_array($columns) ? $columns : [$columns];
        return $this;
    }

    /**
     * Add a WHERE condition
     *
     * @param string $column The column name
     * @param mixed $value The value to compare with
     * @param string $operator The comparison operator
     * @return self
     */
    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $paramName = 'p' . count($this->parts['params']);
        $this->parts['where'][] = "$column $operator :$paramName";
        $this->parts['params'][$paramName] = $value;
        return $this;
    }

    /**
     * Add a raw WHERE condition
     *
     * @param string $condition The raw condition
     * @param array $params The parameters for the condition
     * @return self
     */
    public function whereRaw(string $condition, array $params = []): self
    {
        $this->parts['where'][] = $condition;

        foreach ($params as $key => $value) {
            $this->parts['params'][$key] = $value;
        }

        return $this;
    }

    /**
     * Add an ORDER BY clause
     *
     * @param string $column The column to order by
     * @param string $direction The order direction (ASC or DESC)
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->parts['order'][] = "$column " . strtoupper($direction);
        return $this;
    }

    /**
     * Set the LIMIT clause
     *
     * @param int $limit The maximum number of rows to return
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->parts['limit'] = $limit;
        return $this;
    }

    /**
     * Set the OFFSET clause
     *
     * @param int $offset The number of rows to skip
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->parts['offset'] = $offset;
        return $this;
    }

    /**
     * Build the SQL query
     *
     * @return string The SQL query
     */
    public function buildQuery(): string
    {
        if (empty($this->table)) {
            throw new \Exception("Table name is required");
        }

        $query = "SELECT " . implode(', ', $this->parts['select']) . " FROM " . $this->table;

        if (!empty($this->parts['where'])) {
            $query .= " WHERE " . implode(' AND ', $this->parts['where']);
        }

        if (!empty($this->parts['order'])) {
            $query .= " ORDER BY " . implode(', ', $this->parts['order']);
        }

        if ($this->parts['limit'] !== null) {
            $query .= " LIMIT " . $this->parts['limit'];

            if ($this->parts['offset'] !== null) {
                $query .= " OFFSET " . $this->parts['offset'];
            }
        }

        return $query;
    }

    /**
     * Get the parameters for the query
     *
     * @return array The query parameters
     */
    public function getParams(): array
    {
        return $this->parts['params'];
    }

    /**
     * Execute the query and return the result
     *
     * @return mixed The query result
     */
    public function get(): mixed
    {
        $query = $this->buildQuery();
        return $this->db->query($query, $this->parts['params']);
    }

    /**
     * Execute the query and return the first result
     *
     * @return mixed The first result or null if no results
     */
    public function first(): mixed
    {
        $this->limit(1);
        $result = $this->get();

        if (is_array($result) && count($result) > 0) {
            return $result[0];
        }

        return null;
    }

    /**
     * Insert a record into the table
     *
     * @param array $data The data to insert
     * @return mixed The query result
     */
    public function insert(array $data): mixed
    {
        if (empty($this->table)) {
            throw new \Exception("Table name is required");
        }

        $columns = array_keys($data);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $query = "INSERT INTO " . $this->table . " (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";

        return $this->db->query($query, $data);
    }

    /**
     * Update records in the table
     *
     * @param array $data The data to update
     * @return mixed The query result
     */
    public function update(array $data): mixed
    {
        if (empty($this->table)) {
            throw new \Exception("Table name is required");
        }

        if (empty($this->parts['where'])) {
            throw new \Exception("WHERE clause is required for UPDATE");
        }

        $sets = [];
        $params = $this->parts['params'];

        foreach ($data as $column => $value) {
            $paramName = 'u' . count($params);
            $sets[] = "$column = :$paramName";
            $params[$paramName] = $value;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(', ', $sets);

        if (!empty($this->parts['where'])) {
            $query .= " WHERE " . implode(' AND ', $this->parts['where']);
        }

        return $this->db->query($query, $params);
    }

    /**
     * Delete records from the table
     *
     * @return mixed The query result
     */
    public function delete(): mixed
    {
        if (empty($this->table)) {
            throw new \Exception("Table name is required");
        }

        if (empty($this->parts['where'])) {
            throw new \Exception("WHERE clause is required for DELETE");
        }

        $query = "DELETE FROM " . $this->table;

        if (!empty($this->parts['where'])) {
            $query .= " WHERE " . implode(' AND ', $this->parts['where']);
        }

        return $this->db->query($query, $this->parts['params']);
    }
}
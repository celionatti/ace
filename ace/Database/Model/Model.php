<?php

declare(strict_types=1);

namespace Ace\ace\Database\Model;

use Ace\ace\Database\Database;
use Ace\ace\Database\QueryBuilder\QueryBuilder;
use Ace\ace\Validation\Validator;

abstract class Model
{
    use Pagination;

    /**
     * @var string The table name associated with the model
     */
    protected string $table;

    /**
     * @var string The primary key column name
     */
    protected string $primaryKey = 'id';

    /**
     * @var array Fillable attributes that can be mass-assigned
     */
    protected array $fillable = [];

    /**
     * @var array Hidden attributes that should be excluded from serialization
     */
    protected array $hidden = [];

    /**
     * @var array Validation rules for model attributes
     */
    protected array $rules = [];

    /**
     * @var array The current model attributes
     */
    protected array $attributes = [];

    /**
     * @var array Original attributes before any modifications
     */
    protected array $original = [];

    /**
     * @var array Changed attributes
     */
    protected array $changes = [];

    /**
     * @var bool Whether the model exists in the database
     */
    protected bool $exists = false;

    /**
     * Constructor
     *
     * @param array $attributes Initial model attributes
     */
    public function __construct(array $attributes = [])
    {
        // Set the table name if not already set
        if (empty($this->table)) {
            $this->table = strtolower(
                preg_replace('/(?<!^)[A-Z]/', '_$0',
                    (new \ReflectionClass($this))->getShortName() . 's'
                )
            );
        }

        // Fill the model with attributes
        if (!empty($attributes)) {
            $this->fill($attributes);
        }
    }

    /**
     * Fill the model with an array of attributes
     *
     * @param array $attributes Attributes to fill
     * @return self
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
        return $this;
    }

    /**
     * Set an attribute
     *
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     * @return self
     */
    public function setAttribute(mixed $key, mixed $value): self
    {
        // Only allow fillable attributes
        if (empty($this->fillable) || in_array($key, $this->fillable)) {
            // Track changes
            if (!isset($this->original[$key]) || $this->original[$key] !== $value) {
                $this->changes[$key] = $value;
            }

            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Get an attribute
     *
     * @param string $key Attribute name
     * @return mixed Attribute value
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Magic method to get attributes
     *
     * @param string $key Attribute name
     * @return mixed Attribute value
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic method to set attributes
     *
     * @param string $key Attribute name
     * @param mixed $value Attribute value
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Validate the model attributes
     *
     * @return bool True if valid, false otherwise
     */
    public function validate(): bool
    {
        // If no validation rules, consider it valid
        if (empty($this->rules)) {
            return true;
        }

        // Use a validation class (you'd need to implement this)
        $validator = new Validator($this->attributes, $this->rules);
        return $validator->passes();
    }

    /**
     * Save the model to the database
     *
     * @return bool True if saved successfully, false otherwise
     */
    public function save(): bool
    {
        // Validate the model
        if (!$this->validate()) {
            return false;
        }

        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return false;
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        try {
            // Begin transaction
            $db->beginTransaction();

            if ($this->exists) {
                // Update existing record
                $result = $builder->table($this->table)
                    ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
                    ->update($this->changes);
            } else {
                // Insert new record
                $result = $builder->table($this->table)
                    ->insert($this->attributes);

                // Set the ID for the new record
                if ($result !== false) {
                    $newId = $db->lastInsertId();
                    $this->setAttribute($this->primaryKey, $newId);
                    $this->exists = true;
                }
            }

            // Commit transaction
            $db->commit();

            // Reset changes after successful save
            $this->original = $this->attributes;
            $this->changes = [];

            return $result !== false;
        } catch (\Exception $e) {
            // Rollback transaction
            $db->rollback();

            // Log the error
            if (class_exists('\Ace\ace\Logger\Logger')) {
                \Ace\ace\Logger\Logger::exception($e);
            }

            return false;
        }
    }

    /**
     * Delete the model from the database
     *
     * @return bool True if deleted successfully, false otherwise
     */
    public function delete(): bool
    {
        // Ensure the model exists in the database
        if (!$this->exists) {
            return false;
        }

        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return false;
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        try {
            // Begin transaction
            $db->beginTransaction();

            // Delete the record
            $result = $builder->table($this->table)
                ->where($this->primaryKey, $this->getAttribute($this->primaryKey))
                ->delete();

            // Commit transaction
            $db->commit();

            // Reset model state if deleted successfully
            if ($result !== false) {
                $this->exists = false;
                $this->attributes = [];
                $this->changes = [];
                $this->original = [];
            }

            return $result !== false;
        } catch (\Exception $e) {
            // Rollback transaction
            $db->rollback();

            // Log the error
            if (class_exists('\App\Database\Logger')) {
                \App\Database\Logger::exception($e);
            }

            return false;
        }
    }

    /**
     * Find a model by its primary key
     *
     * @param mixed $id Primary key value
     * @return static|null The found model or null
     */
    public static function find(mixed $id): ?self
    {
        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return null;
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        // Get the called class
        $className = static::class;

        // Create a new instance
        $model = new $className();

        // Find the record
        $result = $builder->table($model->table)
            ->where($model->primaryKey, $id)
            ->first();

        // Return null if no record found
        if (!$result) {
            return null;
        }

        // Create a model instance with the found data
        $instance = new $className($result);
        $instance->exists = true;
        $instance->original = $result;

        return $instance;
    }

    /**
     * Find models matching given conditions
     *
     * @param array $conditions Conditions to filter by
     * @return array Array of model instances
     */
    public static function where(array $conditions): array
    {
        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return [];
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        // Get the called class
        $className = static::class;

        // Create a new instance
        $model = new $className();

        // Apply conditions
        $query = $builder->table($model->table);
        foreach ($conditions as $column => $value) {
            $query->where($column, $value);
        }

        // Execute the query
        $results = $query->get();

        // Convert results to model instances
        if (!$results) {
            return [];
        }

        return array_map(function($result) use ($className) {
            $instance = new $className($result);
            $instance->exists = true;
            $instance->original = $result;
            return $instance;
        }, $results);
    }

    /**
     * Get all records for the model
     *
     * @return array Array of model instances
     */
    public static function all(): array
    {
        // Get the database connection
        $db = Database::getInstance();
        if (!$db) {
            return [];
        }

        // Create a query builder
        $builder = new QueryBuilder($db);

        // Get the called class
        $className = static::class;

        // Create a new instance
        $model = new $className();

        // Get all records
        $results = $builder->table($model->table)->get();

        // Convert results to model instances
        if (!$results) {
            return [];
        }

        return array_map(function($result) use ($className) {
            $instance = new $className($result);
            $instance->exists = true;
            $instance->original = $result;
            return $instance;
        }, $results);
    }

    /**
     * Create a new model instance and save it
     *
     * @param array $attributes Attributes for the new model
     * @return static|null The created model or null on failure
     */
    public static function create(array $attributes): ?self
    {
        // Get the called class
        $className = static::class;

        // Create a new instance
        $model = new $className($attributes);

        // Save the model
        return $model->save() ? $model : null;
    }

    /**
     * Convert the model to an array
     *
     * @return array Model attributes as an array
     */
    public function toArray(): array
    {
        // Remove hidden attributes
        $attributes = $this->attributes;
        foreach ($this->hidden as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }

    /**
     * Convert the model to a JSON string
     *
     * @return string JSON representation of the model
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Magic method for JSON serialization
     *
     * @return array Serializable data
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}

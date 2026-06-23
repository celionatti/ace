<?php

namespace Ace;

use Ace\Exceptions\ModelNotFoundException;

abstract class Model
{
    public const RULE_REQUIRED = 'required';
    public const RULE_EMAIL = 'email';
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_MATCH = 'match';
    public const RULE_UNIQUE = 'unique';

    protected array $attributes = [];
    public array $errors = [];

    /**
     * Start a new fluent query builder instance for the model
     */
    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::class);
    }

    abstract public static function tableName(): string;
    abstract public function primaryKey(): string;
    abstract public function rules(): array;

    /**
     * Get class attributes dynamically mapping to DB table columns if not overridden
     */
    public function attributes(): array
    {
        return [];
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->attributes[$name]);
    }

    public function __unset(string $name): void
    {
        unset($this->attributes[$name]);
    }

    /**
     * Populate model attributes with raw request/db array data
     */
    public function loadData(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Perform validations defined in model rules
     */
    public function validate(): bool
    {
        $this->errors = [];

        foreach ($this->rules() as $attribute => $rules) {
            $value = $this->{$attribute} ?? null;
            
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            
            foreach ($rules as $rule) {
                $ruleName = $rule;
                $ruleData = [];
                
                if (is_string($rule)) {
                    if (str_contains($rule, ':')) {
                        [$name, $paramStr] = explode(':', $rule, 2);
                        $ruleName = $name;
                        
                        if ($ruleName === self::RULE_MIN) {
                            $ruleData = ['min' => (int)$paramStr];
                        } elseif ($ruleName === self::RULE_MAX) {
                            $ruleData = ['max' => (int)$paramStr];
                        } elseif ($ruleName === self::RULE_MATCH) {
                            $ruleData = ['match' => $paramStr];
                        } elseif ($ruleName === self::RULE_UNIQUE) {
                            $parts = explode(',', $paramStr);
                            $ruleData = [
                                'table' => $parts[0],
                                'column' => $parts[1] ?? $attribute
                            ];
                        }
                    }
                } elseif (is_array($rule)) {
                    $ruleName = $rule[0];
                    $ruleData = $rule;
                }

                if ($ruleName === self::RULE_REQUIRED && (is_null($value) || $value === '')) {
                    $this->addErrorForRule($attribute, self::RULE_REQUIRED);
                }

                if ($ruleName === self::RULE_EMAIL && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addErrorForRule($attribute, self::RULE_EMAIL);
                }

                if ($ruleName === self::RULE_MIN && !empty($value) && strlen((string)$value) < ($ruleData['min'] ?? 0)) {
                    $this->addErrorForRule($attribute, self::RULE_MIN, $ruleData);
                }

                if ($ruleName === self::RULE_MAX && !empty($value) && strlen((string)$value) > ($ruleData['max'] ?? INF)) {
                    $this->addErrorForRule($attribute, self::RULE_MAX, $ruleData);
                }

                if ($ruleName === self::RULE_MATCH && $value !== $this->{$ruleData['match'] ?? ''}) {
                    $ruleData['matchLabel'] = $ruleData['match'] ?? '';
                    $this->addErrorForRule($attribute, self::RULE_MATCH, $ruleData);
                }

                if ($ruleName === self::RULE_UNIQUE && !empty($value)) {
                    $tableName = $ruleData['table'] ?? null;
                    if (!$tableName) {
                        $className = $ruleData['class'] ?? static::class;
                        $tableName = $className::tableName();
                    }
                    $uniqueAttr = $ruleData['column'] ?? $ruleData['attribute'] ?? $attribute;
                    $db = Application::$app->db;

                    if ($db) {
                        $sql = "SELECT * FROM `$tableName` WHERE `$uniqueAttr` = :$uniqueAttr";
                        $params = [":$uniqueAttr" => $value];
                        
                        $pk = $this->primaryKey();
                        $pkValue = $this->{$pk} ?? null;
                        if (!empty($pkValue)) {
                            $sql .= " AND `$pk` != :pk_val";
                            $params[":pk_val"] = $pkValue;
                        }

                        $statement = $db->prepare($sql);
                        $statement->execute($params);
                        if ($statement->fetch()) {
                            $this->addErrorForRule($attribute, self::RULE_UNIQUE, ['field' => $attribute]);
                        }
                    }
                }
            }
        }

        return empty($this->errors);
    }

    /**
     * Add standard or custom error messages
     */
    private function addErrorForRule(string $attribute, string $ruleName, array $params = []): void
    {
        $message = $this->errorMessages()[$ruleName] ?? '';
        foreach ($params as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        $this->errors[$attribute][] = $message;
    }

    public function addError(string $attribute, string $message): void
    {
        $this->errors[$attribute][] = $message;
    }

    public function hasError(string $attribute): bool
    {
        return !empty($this->errors[$attribute]);
    }

    public function getFirstError(string $attribute): string
    {
        return $this->errors[$attribute][0] ?? '';
    }

    private function errorMessages(): array
    {
        return [
            self::RULE_REQUIRED => 'This field is required',
            self::RULE_EMAIL => 'This field must be a valid email address',
            self::RULE_MIN => 'Min length of this field must be {min}',
            self::RULE_MAX => 'Max length of this field must be {max}',
            self::RULE_MATCH => 'This field must be the same as {matchLabel}',
            self::RULE_UNIQUE => 'Record with this {field} already exists',
        ];
    }

    /**
     * Active Record Save (Insert or Update)
     */
    public function save(): bool
    {
        $tableName = static::tableName();
        $primaryKey = $this->primaryKey();
        $db = Application::$app->db;

        if (!$db) {
            return false;
        }

        // Get table columns to filter attributes
        $columns = $this->attributes();
        if (empty($columns)) {
            $columns = $db->getTableColumns($tableName);
        }

        $saveData = [];
        foreach ($columns as $column) {
            if ($column === $primaryKey && empty($this->{$primaryKey})) {
                continue; // Skip primary key on insert
            }
            if (isset($this->attributes[$column])) {
                $saveData[$column] = $this->attributes[$column];
            }
        }

        $pkValue = $this->{$primaryKey} ?? null;

        if (empty($pkValue)) {
            // INSERT record
            $fields = array_keys($saveData);
            $placeholders = array_map(fn($f) => ":$f", $fields);
            
            $sql = "INSERT INTO `$tableName` (" . 
                implode(',', array_map(fn($f) => "`$f`", $fields)) . 
                ") VALUES (" . 
                implode(',', $placeholders) . ")";

            $statement = $db->prepare($sql);
            foreach ($saveData as $key => $value) {
                $statement->bindValue(":$key", $value);
            }

            if ($statement->execute()) {
                $this->{$primaryKey} = $db->pdo->lastInsertId();
                return true;
            }
        } else {
            // UPDATE record
            $fields = array_keys($saveData);
            $setStatements = array_map(fn($f) => "`$f` = :$f", $fields);
            
            $sql = "UPDATE `$tableName` SET " . 
                implode(',', $setStatements) . 
                " WHERE `$primaryKey` = :pk_val";

            $statement = $db->prepare($sql);
            foreach ($saveData as $key => $value) {
                $statement->bindValue(":$key", $value);
            }
            $statement->bindValue(':pk_val', $pkValue);

            return $statement->execute();
        }

        return false;
    }

    /**
     * Active Record Find Single Record
     */
    public static function findOne(array $where): ?static
    {
        $tableName = static::tableName();
        $db = Application::$app->db;
        
        if (!$db) {
            return null;
        }

        $sql = "SELECT * FROM `$tableName`";
        if (!empty($where)) {
            $sql .= " WHERE ";
            $attributes = array_keys($where);
            $whereParts = array_map(fn($attr) => "`$attr` = :$attr", $attributes);
            $sql .= implode(' AND ', $whereParts);
        }
        $sql .= " LIMIT 1";

        $statement = $db->prepare($sql);
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        $record = $statement->fetch();

        if (!$record) {
            return null;
        }

        $model = new static();
        $model->loadData($record);
        return $model;
    }

    /**
     * Active Record Find Multiple Records
     */
    public static function find(array $where = [], array $options = []): array
    {
        $tableName = static::tableName();
        $db = Application::$app->db;

        if (!$db) {
            return [];
        }

        $sql = "SELECT * FROM `$tableName`";
        if (!empty($where)) {
            $sql .= " WHERE ";
            $attributes = array_keys($where);
            $whereParts = array_map(fn($attr) => "`$attr` = :$attr", $attributes);
            $sql .= implode(' AND ', $whereParts);
        }

        if (!empty($options['order_by'])) {
            $sql .= " ORDER BY " . $options['order_by'];
        }

        if (isset($options['limit'])) {
            $sql .= " LIMIT " . (int)$options['limit'];
        }
        if (isset($options['offset'])) {
            $sql .= " OFFSET " . (int)$options['offset'];
        }

        $statement = $db->prepare($sql);
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        $records = $statement->fetchAll();

        $models = [];
        foreach ($records as $record) {
            $model = new static();
            $model->loadData($record);
            $models[] = $model;
        }

        return $models;
    }

    public static function all(): array
    {
        return static::find();
    }

    // =========================================================================
    // Find-or-Fail Methods
    // =========================================================================

    /**
     * Find a record by primary key or throw ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public static function findOrFail(mixed $id): static
    {
        $model = new static();
        $pk = $model->primaryKey();
        $record = static::findOne([$pk => $id]);

        if (!$record) {
            throw new ModelNotFoundException(static::class, $id);
        }

        return $record;
    }

    /**
     * Find the first record matching conditions or throw ModelNotFoundException.
     *
     * @throws ModelNotFoundException
     */
    public static function firstOrFail(array $where): static
    {
        $record = static::findOne($where);

        if (!$record) {
            throw new ModelNotFoundException(static::class);
        }

        return $record;
    }

    // =========================================================================
    // Convenient Finders
    // =========================================================================

    /**
     * Find records where a single column matches a value.
     *
     *   User::findBy('email', 'john@example.com');
     */
    public static function findBy(string $column, mixed $value): array
    {
        return static::find([$column => $value]);
    }

    /**
     * Check if any record exists matching the given conditions.
     *
     *   if (User::exists(['email' => $email])) { ... }
     */
    public static function exists(array $where): bool
    {
        return static::count($where) > 0;
    }

    /**
     * Get the latest records, ordered by a column (default: primary key).
     *
     *   Post::latest();               // ORDER BY id DESC
     *   Post::latest('created_at');    // ORDER BY created_at DESC
     */
    public static function latest(string $column = null, int $limit = null): array
    {
        $model = new static();
        $col = $column ?? $model->primaryKey();
        $options = ['orderBy' => "`$col` DESC"];
        if ($limit !== null) {
            $options['limit'] = $limit;
        }
        return static::find([], $options);
    }

    /**
     * Get the oldest records, ordered by a column ascending.
     *
     *   Post::oldest('created_at');
     */
    public static function oldest(string $column = null, int $limit = null): array
    {
        $model = new static();
        $col = $column ?? $model->primaryKey();
        $options = ['orderBy' => "`$col` ASC"];
        if ($limit !== null) {
            $options['limit'] = $limit;
        }
        return static::find([], $options);
    }

    /**
     * Pluck a single column's values from matching records.
     *
     *   $emails = User::pluck('email');                      // ['a@b.com', 'c@d.com']
     *   $map    = User::pluck('email', 'id');                // [1 => 'a@b.com', 2 => 'c@d.com']
     */
    public static function pluck(string $column, string $keyColumn = null, array $where = []): array
    {
        $records = static::find($where);
        $result = [];

        foreach ($records as $record) {
            if ($keyColumn !== null) {
                $result[$record->{$keyColumn}] = $record->{$column};
            } else {
                $result[] = $record->{$column};
            }
        }

        return $result;
    }

    // =========================================================================
    // Static Factory Methods
    // =========================================================================

    /**
     * Create a new record and persist it immediately.
     *
     *   $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
     */
    public static function create(array $data): static
    {
        $model = new static();
        $model->loadData($data);
        $model->save();
        return $model;
    }

    /**
     * Find the first record matching attributes, or create it.
     *
     *   $user = User::firstOrCreate(
     *       ['email' => 'john@example.com'],       // search by
     *       ['name' => 'John Doe']                  // extra data for creation
     *   );
     */
    public static function firstOrCreate(array $where, array $extra = []): static
    {
        $existing = static::findOne($where);

        if ($existing) {
            return $existing;
        }

        return static::create(array_merge($where, $extra));
    }

    /**
     * Find and update or create if not found.
     *
     *   $user = User::updateOrCreate(
     *       ['email' => 'john@example.com'],       // search by
     *       ['name' => 'John Doe', 'role' => 'admin']  // data to update/set
     *   );
     */
    public static function updateOrCreate(array $where, array $data): static
    {
        $existing = static::findOne($where);

        if ($existing) {
            $existing->fill($data);
            $existing->save();
            return $existing;
        }

        return static::create(array_merge($where, $data));
    }

    /**
     * Delete record(s) by primary key.
     *
     *   User::destroy(1);
     *   User::destroy(1, 2, 3);
     *   User::destroy([1, 2, 3]);
     */
    public static function destroy(mixed ...$ids): int
    {
        $ids = is_array($ids[0] ?? null) ? $ids[0] : $ids;
        $deleted = 0;

        foreach ($ids as $id) {
            try {
                $model = static::findOrFail($id);
                if ($model->delete()) {
                    $deleted++;
                }
            } catch (ModelNotFoundException) {
                // Skip missing records
            }
        }

        return $deleted;
    }

    // =========================================================================
    // Instance Helpers
    // =========================================================================

    /**
     * Mass-assign attributes (without saving).
     *
     *   $user->fill(['name' => 'Jane', 'email' => 'jane@example.com']);
     */
    public function fill(array $data): static
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
        return $this;
    }

    /**
     * Mass-assign attributes and save immediately.
     *
     *   $user->update(['name' => 'Updated Name']);
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    /**
     * Reload the model's attributes from the database.
     *
     *   $user->fresh();  // Re-fetches from DB
     */
    public function fresh(): ?static
    {
        $pk = $this->primaryKey();
        $pkValue = $this->{$pk} ?? null;

        if (empty($pkValue)) {
            return null;
        }

        return static::findOne([$pk => $pkValue]);
    }

    /**
     * Reload this instance in-place from the database.
     *
     *   $user->refresh();  // Same instance, updated attributes
     */
    public function refresh(): static
    {
        $fresh = $this->fresh();

        if ($fresh) {
            $this->attributes = $fresh->attributes;
        }

        return $this;
    }

    /**
     * Convert the model's attributes to a plain array.
     *
     *   $array = $user->toArray();
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert the model's attributes to a JSON string.
     *
     *   echo $user->toJson();
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Check if the model has been persisted (has a primary key value).
     *
     *   if ($user->isPersisted()) { ... }
     */
    public function isPersisted(): bool
    {
        $pk = $this->primaryKey();
        return !empty($this->{$pk});
    }

    /**
     * Duplicate the model instance (without the primary key).
     *
     *   $clone = $user->replicate();
     *   $clone->email = 'new@email.com';
     *   $clone->save();
     */
    public function replicate(): static
    {
        $clone = new static();
        $pk = $this->primaryKey();

        foreach ($this->attributes as $key => $value) {
            if ($key !== $pk) {
                $clone->{$key} = $value;
            }
        }

        return $clone;
    }

    /**
     * Get matching records count
     */
    public static function count(array $where = []): int
    {
        $tableName = static::tableName();
        $db = Application::$app->db;

        if (!$db) {
            return 0;
        }

        $sql = "SELECT COUNT(*) FROM `$tableName`";
        if (!empty($where)) {
            $sql .= " WHERE ";
            $attributes = array_keys($where);
            $whereParts = array_map(fn($attr) => "`$attr` = :$attr", $attributes);
            $sql .= implode(' AND ', $whereParts);
        }

        $statement = $db->prepare($sql);
        foreach ($where as $key => $value) {
            $statement->bindValue(":$key", $value);
        }

        $statement->execute();
        return (int)$statement->fetchColumn();
    }

    /**
     * Paginate queries, returning metadata and records
     */
    public static function paginate(int $perPage = 15, int $page = 1, array $where = [], array $options = []): array
    {
        $total = static::count($where);
        $lastPage = (int)ceil($total / $perPage);
        $page = max(1, min($page, $lastPage === 0 ? 1 : $lastPage));
        $offset = ($page - 1) * $perPage;

        $options['limit'] = $perPage;
        $options['offset'] = $offset;

        $data = static::find($where, $options);

        return [
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'next_page' => $page < $lastPage ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null,
        ];
    }

    /**
     * Chunk queries for large dataset processing
     */
    public static function chunk(int $size, callable $callback, array $where = [], array $options = []): void
    {
        $page = 1;
        do {
            $offset = ($page - 1) * $size;
            $options['limit'] = $size;
            $options['offset'] = $offset;
            
            $records = static::find($where, $options);
            
            if (empty($records)) {
                break;
            }

            if ($callback($records, $page) === false) {
                break;
            }

            $page++;
        } while (count($records) === $size);
    }

    /**
     * Active Record Delete Record
     */
    public function delete(): bool
    {
        $tableName = static::tableName();
        $primaryKey = $this->primaryKey();
        $pkValue = $this->{$primaryKey} ?? null;

        if (empty($pkValue)) {
            return false;
        }

        $db = Application::$app->db;
        if (!$db) {
            return false;
        }

        $statement = $db->prepare("DELETE FROM `$tableName` WHERE `$primaryKey` = :pk");
        $statement->bindValue(':pk', $pkValue);
        return $statement->execute();
    }
}


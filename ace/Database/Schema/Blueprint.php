<?php

declare(strict_types=1);

namespace Ace\ace\Database\Schema;

class Blueprint
{
    /**
     * The table name
     *
     * @var string
     */
    protected $table;

    /**
     * Whether this is a table modification
     *
     * @var bool
     */
    protected $modifying;

    /**
     * The columns to be created
     *
     * @var array
     */
    protected $columns = [];

    /**
     * The indexes to be created
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * Columns to be dropped
     *
     * @var array
     */
    protected $drops = [];

    /**
     * Indexes to be dropped
     *
     * @var array
     */
    protected $dropsIndexes = [];

    /**
     * Create a new blueprint instance
     *
     * @param string $table
     * @param bool $modifying
     * @return void
     */
    public function __construct($table, $modifying = false)
    {
        $this->table = $table;
        $this->modifying = $modifying;
    }

    /**
     * Add an ID column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function id($name = 'id')
    {
        return $this->integer($name)->unsigned()->autoIncrement()->primary();
    }

    /**
     * Add an integer column
     *
     * @param string $name
     * @param bool $unsigned
     * @return ColumnDefinition
     */
    public function integer($name, $unsigned = false)
    {
        return $this->addColumn('integer', $name, compact('unsigned'));
    }

    /**
     * Add a string column
     *
     * @param string $name
     * @param int $length
     * @return ColumnDefinition
     */
    public function string($name, $length = 255)
    {
        return $this->addColumn('string', $name, compact('length'));
    }

    /**
     * Add a text column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function text($name)
    {
        return $this->addColumn('text', $name);
    }

    /**
     * Add a boolean column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function boolean($name)
    {
        return $this->addColumn('boolean', $name);
    }

    /**
     * Add a datetime column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function datetime($name)
    {
        return $this->addColumn('datetime', $name);
    }

    /**
     * Add a date column
     *
     * @param string $name
     * @return ColumnDefinition
     */
    public function date($name)
    {
        return $this->addColumn('date', $name);
    }

    /**
     * Add a decimal column
     *
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @return ColumnDefinition
     */
    public function decimal($name, $precision = 8, $scale = 2)
    {
        return $this->addColumn('decimal', $name, compact('precision', 'scale'));
    }

    /**
     * Add timestamp columns
     *
     * @return void
     */
    public function timestamps()
    {
        $this->datetime('created_at')->nullable();
        $this->datetime('updated_at')->nullable();
    }

    /**
     * Add a primary key
     *
     * @param string|array $columns
     * @return void
     */
    public function primary($columns)
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->indexes[] = [
            'type' => 'primary',
            'columns' => $columns
        ];
    }

    /**
     * Add an index
     *
     * @param string|array $columns
     * @param string|null $name
     * @return void
     */
    public function index($columns, $name = null)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        // Generate index name if not provided
        if ($name === null) {
            $name = $this->table . '_' . implode('_', $columns) . '_index';
        }

        $this->indexes[] = [
            'type' => 'index',
            'columns' => $columns,
            'name' => $name
        ];
    }

    /**
     * Add a unique index
     *
     * @param string|array $columns
     * @param string|null $name
     * @return void
     */
    public function unique($columns, $name = null)
    {
        $columns = is_array($columns) ? $columns : func_get_args();

        // Generate unique index name if not provided
        if ($name === null) {
            $name = $this->table . '_' . implode('_', $columns) . '_unique';
        }

        $this->indexes[] = [
            'type' => 'unique',
            'columns' => $columns,
            'name' => $name
        ];
    }

    /**
     * Add a foreign key constraint
     *
     * @param string|array $columns
     * @param string $table
     * @param string|array $references
     * @return ForeignKeyDefinition
     */
    public function foreign($columns, $table = null, $references = null)
    {
        $columns = is_array($columns) ? $columns : [$columns];

        if ($table === null) {
            return new ForeignKeyDefinition($this, $columns);
        }

        $references = is_array($references) ? $references : [$references];

        $this->indexes[] = [
            'type' => 'foreign',
            'columns' => $columns,
            'table' => $table,
            'references' => $references,
            'onDelete' => 'RESTRICT',
            'onUpdate' => 'RESTRICT'
        ];
    }

    /**
     * Drop a column
     *
     * @param string $name
     * @return $this
     */
    public function dropColumn($name)
    {
        $this->drops[] = $name;
        return $this;
    }

    /**
     * Drop an index
     *
     * @param string $name
     * @return $this
     */
    public function dropIndex($name)
    {
        $this->dropsIndexes[] = ['type' => 'index', 'name' => $name];
        return $this;
    }

    /**
     * Drop a unique constraint
     *
     * @param string $name
     * @return $this
     */
    public function dropUnique($name)
    {
        $this->dropsIndexes[] = ['type' => 'unique', 'name' => $name];
        return $this;
    }

    /**
     * Drop a foreign key constraint
     *
     * @param string $name
     * @return $this
     */
    public function dropForeign($name)
    {
        $this->dropsIndexes[] = ['type' => 'foreign', 'name' => $name];
        return $this;
    }

    /**
     * Add a column definition
     *
     * @param string $type
     * @param string $name
     * @param array $parameters
     * @return ColumnDefinition
     */
    protected function addColumn($type, $name, array $parameters = [])
    {
        $column = new ColumnDefinition($type, $name, $parameters);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Generate SQL for the blueprint
     *
     * @return string
     */
    public function toSql()
    {
        $connection = Schema::getConnection();
        $driver = $connection->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($this->modifying) {
            return $this->toAlterSql($driver);
        }

        return $this->toCreateSql($driver);
    }

    /**
     * Generate CREATE TABLE SQL for the blueprint
     *
     * @param string $driver
     * @return string
     */
    protected function toCreateSql($driver)
    {
        $columns = [];

        foreach ($this->columns as $column) {
            $columns[] = "  " . $column->toSql($driver);
        }

        // Add primary keys
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'primary') {
                $columns[] = "  PRIMARY KEY (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ")";
            }
        }

        $sql = "CREATE TABLE `{$this->table}` (\n" . implode(",\n", $columns) . "\n)";

        // MySQL specific settings
        if ($driver === 'mysql') {
            $sql .= " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        }

        $sql .= ";";

        // Add indexes and foreign keys
        $indexSql = [];
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'index') {
                $indexSql[] = "CREATE INDEX `{$index['name']}` ON `{$this->table}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ");";
            } elseif ($index['type'] === 'unique') {
                $indexSql[] = "CREATE UNIQUE INDEX `{$index['name']}` ON `{$this->table}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ");";
            } elseif ($index['type'] === 'foreign') {
                $fkName = $this->table . '_' . implode('_', $index['columns']) . '_foreign';
                $indexSql[] = "ALTER TABLE `{$this->table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ") REFERENCES `{$index['table']}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['references'])) . ") ON DELETE {$index['onDelete']} ON UPDATE {$index['onUpdate']};";
            }
        }

        if (!empty($indexSql)) {
            $sql .= "\n" . implode("\n", $indexSql);
        }

        return $sql;
    }

    /**
     * Generate ALTER TABLE SQL for the blueprint
     *
     * @param string $driver
     * @return string
     */
    protected function toAlterSql($driver)
    {
        $statements = [];

        // Drop columns
        foreach ($this->drops as $column) {
            $statements[] = "ALTER TABLE `{$this->table}` DROP COLUMN `{$column}`;";
        }

        // Add/modify columns
        foreach ($this->columns as $column) {
            $statements[] = "ALTER TABLE `{$this->table}` ADD COLUMN " . $column->toSql($driver) . ";";
        }

        // Drop indexes and foreign keys
        foreach ($this->dropsIndexes as $drop) {
            switch ($drop['type']) {
                case 'index':
                case 'unique':
                    $statements[] = "DROP INDEX `{$drop['name']}` ON `{$this->table}`;";
                    break;
                case 'foreign':
                    $statements[] = "ALTER TABLE `{$this->table}` DROP FOREIGN KEY `{$drop['name']}`;";
                    break;
            }
        }

        // Add indexes and foreign keys
        foreach ($this->indexes as $index) {
            if ($index['type'] === 'index') {
                $statements[] = "CREATE INDEX `{$index['name']}` ON `{$this->table}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ");";
            } elseif ($index['type'] === 'unique') {
                $statements[] = "CREATE UNIQUE INDEX `{$index['name']}` ON `{$this->table}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ");";
            } elseif ($index['type'] === 'foreign') {
                $fkName = $this->table . '_' . implode('_', $index['columns']) . '_foreign';
                $statements[] = "ALTER TABLE `{$this->table}` ADD CONSTRAINT `{$fkName}` FOREIGN KEY (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['columns'])) . ") REFERENCES `{$index['table']}` (" . implode(', ', array_map(function ($column) {
                    return "`{$column}`";
                }, $index['references'])) . ") ON DELETE {$index['onDelete']} ON UPDATE {$index['onUpdate']};";
            }
        }

        return implode("\n", $statements);
    }
}
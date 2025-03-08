<?php

declare(strict_types=1);

namespace Ace\ace\Database\Schema;

class Schema
{
    /**
     * Database connection
     *
     * @var \PDO
     */
    protected static $connection;

    /**
     * Set the database connection
     *
     * @param \PDO $connection
     * @return void
     */
    public static function setConnection(\PDO $connection)
    {
        self::$connection = $connection;
    }

    /**
     * Get the database connection
     *
     * @return \PDO
     * @throws \Exception
     */
    public static function getConnection()
    {
        if (!self::$connection) {
            throw new \Exception("Database connection not set. Call Schema::setConnection() first.");
        }

        return self::$connection;
    }

    /**
     * Create a new table
     *
     * @param string $tableName
     * @param callable $callback
     * @return void
     */
    public static function create($tableName, callable $callback)
    {
        $blueprint = new Blueprint($tableName);
        $callback($blueprint);

        $sql = $blueprint->toSql();

        try {
            self::getConnection()->exec($sql);
            echo "Table '{$tableName}' created successfully." . PHP_EOL;
        } catch (\PDOException $e) {
            echo "Error creating table '{$tableName}': " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Drop a table if it exists
     *
     * @param string $tableName
     * @return void
     */
    public static function dropIfExists($tableName)
    {
        $sql = "DROP TABLE IF EXISTS `{$tableName}`";

        try {
            self::getConnection()->exec($sql);
            echo "Table '{$tableName}' dropped successfully." . PHP_EOL;
        } catch (\PDOException $e) {
            echo "Error dropping table '{$tableName}': " . $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Check if a table exists
     *
     * @param string $tableName
     * @return bool
     */
    public static function hasTable($tableName)
    {
        $db = self::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                $sql = "SHOW TABLES LIKE '{$tableName}'";
                break;
            case 'sqlite':
                $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='{$tableName}'";
                break;
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }

        $result = $db->query($sql);
        return $result->rowCount() > 0;
    }

    /**
     * Check if a column exists in a table
     *
     * @param string $tableName
     * @param string $columnName
     * @return bool
     */
    public static function hasColumn($tableName, $columnName)
    {
        $db = self::getConnection();
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        switch ($driver) {
            case 'mysql':
                $sql = "SHOW COLUMNS FROM `{$tableName}` LIKE '{$columnName}'";
                break;
            case 'sqlite':
                $sql = "PRAGMA table_info({$tableName})";
                $result = $db->query($sql);
                $columns = $result->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($columns as $column) {
                    if ($column['name'] === $columnName) {
                        return true;
                    }
                }

                return false;
            default:
                throw new \Exception("Unsupported database driver: {$driver}");
        }

        $result = $db->query($sql);
        return $result->rowCount() > 0;
    }

    /**
     * Modify a table
     *
     * @param string $tableName
     * @param callable $callback
     * @return void
     */
    public static function table($tableName, callable $callback)
    {
        $blueprint = new Blueprint($tableName, true);
        $callback($blueprint);

        $sql = $blueprint->toSql();

        if (!empty($sql)) {
            try {
                self::getConnection()->exec($sql);
                echo "Table '{$tableName}' modified successfully." . PHP_EOL;
            } catch (\PDOException $e) {
                echo "Error modifying table '{$tableName}': " . $e->getMessage() . PHP_EOL;
            }
        }
    }
}
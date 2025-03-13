<?php

declare(strict_types=1);

namespace Ace\Database\Schema;

class ColumnDefinition
{
    protected $type;
    protected $name;
    protected $parameters;
    protected $nullable = false;
    protected $default = null;
    protected $autoIncrement = false;
    protected $primary = false;
    protected $comment = '';
    protected $unsigned = false;

    public function __construct($type, $name, $parameters = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->parameters = $parameters;

        if ($type === 'integer' && isset($parameters['unsigned'])) {
            $this->unsigned = $parameters['unsigned'];
        }
    }

    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

    public function default($value)
    {
        $this->default = $value;
        return $this;
    }

    public function autoIncrement()
    {
        $this->autoIncrement = true;
        return $this;
    }

    public function primary()
    {
        $this->primary = true;
        return $this;
    }

    public function unsigned()
    {
        $this->unsigned = true;
        return $this;
    }

    public function comment($text)
    {
        $this->comment = $text;
        return $this;
    }

    public function toSql($driver)
    {
        $sql = "`{$this->name}` " . strtoupper($this->type);

        switch ($this->type) {
            case 'integer':
                if ($this->unsigned) {
                    $sql .= " UNSIGNED";
                }
                break;
            case 'string':
                $length = $this->parameters['length'] ?? 255;
                $sql .= "({$length})";
                break;
            case 'decimal':
                $precision = $this->parameters['precision'] ?? 8;
                $scale = $this->parameters['scale'] ?? 2;
                $sql .= "({$precision}, {$scale})";
                break;
        }

        $sql .= $this->nullable ? " NULL" : " NOT NULL";

        if ($this->default !== null) {
            $default = is_string($this->default) ? "'{$this->default}'" : $this->default;
            $sql .= " DEFAULT {$default}";
        }

        if ($this->autoIncrement) {
            $sql .= " AUTO_INCREMENT";
        }

        if ($driver === 'mysql' && $this->comment) {
            $sql .= " COMMENT '{$this->comment}'";
        }

        return $sql;
    }
}
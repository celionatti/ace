<?php

declare(strict_types=1);

namespace Ace\Database\Schema;

use Ace\Database\Schema\Blueprint;

class ForeignKeyDefinition
{
    protected $blueprint;
    protected $columns;
    protected $onDelete = 'RESTRICT';
    protected $onUpdate = 'RESTRICT';
    protected $referencesTable;
    protected $referencesColumns;

    public function __construct(Blueprint $blueprint, array $columns)
    {
        $this->blueprint = $blueprint;
        $this->columns = $columns;
    }

    public function references($columns)
    {
        $this->referencesColumns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    public function on($table)
    {
        $this->referencesTable = $table;

        $this->blueprint->indexes[] = [
            'type' => 'foreign',
            'columns' => $this->columns,
            'table' => $this->referencesTable,
            'references' => $this->referencesColumns,
            'onDelete' => $this->onDelete,
            'onUpdate' => $this->onUpdate
        ];

        return $this;
    }

    public function onDelete($action)
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate($action)
    {
        $this->onUpdate = $action;
        return $this;
    }
}
<?php

namespace Ace;

abstract class Migration
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function up(): void;
    abstract public function down(): void;
}


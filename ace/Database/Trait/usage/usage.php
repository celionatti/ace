<?php

// Basic pagination
$users = User::paginate();

// Advanced pagination with options
$users = User::paginate([
    'per_page' => 20,
    'page' => 2,
    'columns' => ['id', 'name', 'email'],
    'conditions' => ['status' => 'active'],
    'order_by' => ['created_at' => 'DESC'],
    'search' => 'john',
    'relations' => ['posts', 'comments']
]);

// Recommeded Modification

class User extends Model implements ModelInterface
{
    // Implement search method
    public function applySearch(QueryBuilder $query, string $searchTerm): void
    {
        $query->whereRaw(
            '(name LIKE :search OR email LIKE :search)',
            ['search' => "%$searchTerm%"]
        );
    }

    // Implement table getter
    public function getTable(): string
    {
        return $this->table;
    }
}
<?php

declare(strict_types=1);

namespace Ace\app\models;

use Ace\ace\Database\Model\Model;
use Ace\ace\Database\Interface\ModelInterface;
use Ace\ace\Database\QueryBuilder\QueryBuilder;

class User extends Model implements ModelInterface
{
    protected array $fillable = [
        'first_name',
        'last_name',
        'email',
        'password'
    ];

    /**
     * @var array Hidden attributes
     */
    protected array $hidden = [
        'password'
    ];

    public function applySearch(QueryBuilder $query, string $searchTerm): void
    {
        $query->whereRaw(
            '(first_name LIKE :search OR email LIKE :search)',
            ['search' => "%$searchTerm%"]
        );
    }

    /**
     * Find a user by email
     *
     * @param string $email User email
     * @return static|null The found user or null
     */
    public static function findByEmail(string $email): ?self
    {
        $results = static::where(['email' => $email]);
        return $results ? $results[0] : null;
    }
}
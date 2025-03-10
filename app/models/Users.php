<?php

namespace Ace\app\models;

use Ace\ace\Database\Model\Model;
use Ace\ace\Database\Interface\ModelInterface;
use Ace\ace\Database\QueryBuilder\QueryBuilder;

class Users extends Model implements ModelInterface
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected array $fillable = [
        'name',
        'email',
        'phone',
        'password',
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
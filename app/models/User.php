<?php

declare(strict_types=1);

namespace Ace\app\models;

use Ace\ace\Database\Model\Model;

class User extends Model
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
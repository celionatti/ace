<?php

namespace App\Models;

use Ace\Model;

class Transaction extends Model
{
    public static function tableName(): string
    {
        return 'transactions';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'email' => [self::RULE_REQUIRED, self::RULE_EMAIL],
            'amount' => [self::RULE_REQUIRED],
            'status' => [self::RULE_REQUIRED],
            'reference' => [
                self::RULE_REQUIRED,
                [self::RULE_UNIQUE, 'class' => self::class]
            ]
        ];
    }
}


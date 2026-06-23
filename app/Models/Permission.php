<?php

namespace App\Models;

use Ace\Model;

class Permission extends Model
{
    public static function tableName(): string
    {
        return 'permissions';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'slug' => 'required|unique:permissions,slug',
        ];
    }
}


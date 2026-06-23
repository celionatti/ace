<?php

namespace App\Models;

use Ace\Model;

class Comment extends Model
{
    public static function tableName(): string
    {
        return 'comments';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'content' => 'required|min:2',
            'post_id' => 'required',
            'user_id' => 'required'
        ];
    }
    
    public function author()
    {
        return User::findOne(['id' => $this->user_id]);
    }
}


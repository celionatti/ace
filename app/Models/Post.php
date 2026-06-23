<?php

namespace App\Models;

use Ace\Model;

class Post extends Model
{
    public static function tableName(): string
    {
        return 'posts';
    }

    public function primaryKey(): string
    {
        return 'id';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|min:5',
            'content' => 'required',
            'user_id' => 'required'
        ];
    }
    
    public function author()
    {
        return User::findOne(['id' => $this->user_id]);
    }

    public function comments()
    {
        $comment = new Comment();
        // Use find to get all comments since findOne gets only one
        return $comment->find(['post_id' => $this->id], ['order_by' => 'created_at ASC']);
    }
}


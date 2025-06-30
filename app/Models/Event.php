<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title', 'description', 'city', 'venue', 'dateTime', 'imageUrl', 'price', 'category_id', 'user_id'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class); 
    }

    public function user()
{
    return $this->belongsTo(User::class);
}
}

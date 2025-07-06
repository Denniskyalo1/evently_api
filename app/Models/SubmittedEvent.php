<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubmittedEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'venue', 'city', 'category_id',
        'price', 'dateTime', 'imageUrl', 'user_id'
    ];

    public function user()
{
    return $this->belongsTo(\App\Models\User::class, 'user_id');
}
}

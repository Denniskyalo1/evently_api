<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'phone',
        'status',
        'transaction_reference',


    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

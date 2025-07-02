<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
     protected $fillable = [
        'user_id', 'event_id','payment_id', 'qr_code', 'status', 'used_at',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function event() {
        return $this->belongsTo(Event::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function userDetail()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function contactUserDetail()
    {
        return $this->belongsTo(User::class,'contact_user_id');
    }
}

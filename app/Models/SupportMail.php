<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMail extends Model
{
    use HasFactory;

    public function userDetail(){
        return $this->belongsTo(User::class,'user_id');
    } 
}

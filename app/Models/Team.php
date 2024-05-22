<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    protected $guarded = [];

    /*
    public function requestFields()
    {
        return $this->hasMany(RequestField::class,'recipientId','id');
    } */

   
    public function userDetail(){
        return $this->belongsTo(User::class,'user_id');
    } 

    public function memberDetail(){
        return $this->belongsTo(User::class,'email','email');
    } 
}

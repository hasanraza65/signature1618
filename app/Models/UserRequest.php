<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRequest extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = "requests";

    public function userDetail()
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function signers()
    {
        return $this->hasMany(Signer::class,'request_id','id');
    }

    public function approvers()
    {
        return $this->hasMany(Approver::class,'request_id','id');
    }

    public function logs()
    {
        return $this->hasMany(RequestLog::class,'request_id','id');
    }

    public function reminders()
    {
        return $this->hasMany(RequestReminderDate::class,'request_id','id');
    }

    
}

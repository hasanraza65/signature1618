<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIActivity extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = "ai_activities";

    public function userDetail()
    {
        return $this->belongsTo(User::class,'user_id');
    }
}

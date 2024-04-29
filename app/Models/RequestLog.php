<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function requestDetail()
    {
        return $this->belongsTo(UserRequest::class,'request_id');
    }

}

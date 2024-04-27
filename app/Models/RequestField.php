<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestField extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function radioFields()
    {
        return $this->hasMany(RadioButton::class,'field_id','id');
    }

}

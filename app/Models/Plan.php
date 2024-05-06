<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function planFeatures()
    {
        return $this->hasMany(PlanFeature::class,'plan_id','id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetOTP extends Model
{
    use HasFactory;

    protected $table = "reset_otps";

    protected $guarded = [];
}

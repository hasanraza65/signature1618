<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approver extends Model
{
    use HasFactory;

    public function approverContactDetail(){
        return $this->belongsTo(Contact::class,'recipient_contact_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signer extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function requestFields()
    {
        return $this->hasMany(RequestField::class,'recipientId','recipient_unique_id');
    }

    public function signerContactDetail(){
        return $this->belongsTo(Contact::class,'recipient_contact_id');
    }
}

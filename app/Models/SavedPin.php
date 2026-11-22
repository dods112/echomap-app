<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedPin extends Model
{
    protected $fillable = ['user_id', 'pin_id'];
}
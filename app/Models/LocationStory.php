<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationStory extends Model
{
    protected $fillable = ['place', 'title', 'summary', 'quote', 'quote_author', 'pin_count', 'pins_hash'];
}
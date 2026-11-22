<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $fillable = ['pin_id', 'user_id', 'symbol'];

    public function pin(): BelongsTo
    {
        return $this->belongsTo(Pin::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
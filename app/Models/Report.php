<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = ['pin_id', 'pin_title', 'reporter_id', 'reason', 'status', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function pin(): BelongsTo
    {
        return $this->belongsTo(Pin::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
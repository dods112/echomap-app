<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Pin extends Model
{
    protected $fillable = ['user_id', 'title', 'place', 'body', 'theme', 'visibility', 'lat', 'lng', 'photo_path'];

    protected $casts = ['lat' => 'float', 'lng' => 'float'];

    protected $appends = ['photo_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_pins')->withTimestamps();
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    /** Public pins, your own pins, and Friends pins from people you follow. */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('visibility', 'Public');
            if ($user) {
                $q->orWhere('user_id', $user->id)
                  ->orWhere(function (Builder $f) use ($user) {
                      $f->where('visibility', 'Friends')
                        ->whereIn('user_id', $user->following()->select('users.id'));
                  });
            }
        });
    }

    /** Pins within $meters of a point (Haversine). */
    public function scopeNear(Builder $query, float $lat, float $lng, int $meters = 100): Builder
    {
        $haversine = '(6371000 * acos(least(1, cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat)))))';

        return $query->whereRaw("$haversine <= ?", [$lat, $lng, $lat, $meters]);
    }
}
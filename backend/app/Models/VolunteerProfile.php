<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerProfile extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'name', 'university', 'major', 'city', 'avatar_initials', 'interests'];

    protected $casts = ['interests' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class);
    }

    public function savedEvents(): HasMany
    {
        return $this->hasMany(SavedEvent::class);
    }

    public function savedVolunteerEvents(): BelongsToMany
    {
        return $this->belongsToMany(
            VolunteerEvent::class,
            'saved_events',
            'volunteer_profile_id',
            'event_id'
        )
            ->withPivot(['id', 'created_by', 'updated_by'])
            ->withTimestamps();
    }
}

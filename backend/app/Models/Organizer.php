<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Organizer extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'name', 'type', 'city', 'verified', 'logo_initial', 'rating', 'total_events', 'response_time'];

    protected $casts = ['verified' => 'boolean', 'rating' => 'float', 'total_events' => 'integer'];

    public function events(): HasMany
    {
        return $this->hasMany(VolunteerEvent::class, 'organizer_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizerMember::class);
    }

    public function applications(): HasManyThrough
    {
        return $this->hasManyThrough(
            VolunteerApplication::class,
            VolunteerEvent::class,
            'organizer_id',
            'event_id',
            'id',
            'id'
        );
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organizer_members')
            ->withPivot(['id', 'role', 'created_by', 'updated_by'])
            ->withTimestamps();
    }
}

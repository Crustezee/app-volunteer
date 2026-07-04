<?php

namespace App\Models;

use App\Enums\EventMode;
use App\Enums\EventStatus;
use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VolunteerEvent extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'slug', 'title', 'category_id', 'organizer_id', 'location', 'city', 'mode', 'date', 'start_time', 'end_time', 'duration_hours', 'quota', 'registered', 'status', 'image', 'short_description', 'description', 'benefits', 'skills', 'roles', 'impact_target', 'tags', 'featured'];

    protected $casts = [
        'benefits' => 'array',
        'skills' => 'array',
        'roles' => 'array',
        'tags' => 'array',
        'featured' => 'boolean',
        'duration_hours' => 'integer',
        'quota' => 'integer',
        'registered' => 'integer',
        'mode' => EventMode::class,
        'status' => EventStatus::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VolunteerApplication::class, 'event_id');
    }

    public function savedBy(): HasMany
    {
        return $this->hasMany(SavedEvent::class, 'event_id');
    }
}

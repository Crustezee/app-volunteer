<?php

namespace App\Models;

use App\Models\Concerns\HasAuditColumns;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedEvent extends Model
{
    use HasAuditColumns;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['id', 'event_id', 'volunteer_profile_id'];

    public function event(): BelongsTo
    {
        return $this->belongsTo(VolunteerEvent::class);
    }

    public function volunteerProfile(): BelongsTo
    {
        return $this->belongsTo(VolunteerProfile::class);
    }
}

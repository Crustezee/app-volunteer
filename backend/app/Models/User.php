<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\OrganizerMemberRole;
use App\Models\Concerns\HasAuditColumns;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasAuditColumns, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'city',
        'avatar_initials',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'role',
        'status',
        'city',
        'avatar_initials',
        'email_verified_at',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function volunteerProfile(): HasOne
    {
        return $this->hasOne(VolunteerProfile::class);
    }

    public function organizerMemberships(): HasMany
    {
        return $this->hasMany(OrganizerMember::class);
    }

    public function organizers(): BelongsToMany
    {
        return $this->belongsToMany(Organizer::class, 'organizer_members')
            ->withPivot(['id', 'role', 'created_by', 'updated_by'])
            ->withTimestamps();
    }

    /**
     * @param  list<OrganizerMemberRole|string>  $roles
     */
    public function hasOrganizerRole(Organizer|string $organizer, array $roles): bool
    {
        $organizerId = $organizer instanceof Organizer ? $organizer->id : $organizer;
        $values = array_map(
            fn (OrganizerMemberRole|string $role) => $role instanceof OrganizerMemberRole ? $role->value : $role,
            $roles
        );

        return $this->organizerMemberships()
            ->where('organizer_id', $organizerId)
            ->whereIn('role', $values)
            ->exists();
    }

    public function belongsToOrganizer(Organizer|string $organizer): bool
    {
        $organizerId = $organizer instanceof Organizer ? $organizer->id : $organizer;

        return $this->organizerMemberships()
            ->where('organizer_id', $organizerId)
            ->exists();
    }
}

<?php

namespace App\Enums;

enum OrganizerMemberRole: string
{
    case Owner = 'Owner';
    case Admin = 'Admin';
    case Member = 'Member';

    /**
     * @return list<string>
     */
    public static function managerValues(): array
    {
        return [self::Owner->value, self::Admin->value];
    }
}

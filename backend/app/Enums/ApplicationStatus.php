<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Draft = 'Draft';
    case Submitted = 'Submitted';
    case Accepted = 'Accepted';
    case Waitlisted = 'Waitlisted';
    case Rejected = 'Rejected';
    case Withdrawn = 'Withdrawn';
    case Completed = 'Completed';
}

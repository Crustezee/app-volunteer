<?php

namespace App\Enums;

enum EventStatus: string
{
    case Open = 'Open';
    case NearlyFull = 'Nearly Full';
    case Closed = 'Closed';
    case Cancelled = 'Cancelled';
    case Completed = 'Completed';
}

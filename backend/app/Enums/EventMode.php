<?php

namespace App\Enums;

enum EventMode: string
{
    case Offline = 'Offline';
    case Online = 'Online';
    case Hybrid = 'Hybrid';
}

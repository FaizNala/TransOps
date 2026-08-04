<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Scheduled = 'scheduled';
    case Due = 'due';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';
}

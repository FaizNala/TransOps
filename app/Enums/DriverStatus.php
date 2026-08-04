<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case Inactive = 'inactive';
}

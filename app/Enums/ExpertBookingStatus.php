<?php

namespace App\Enums;

enum ExpertBookingStatus: string
{
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
}

<?php

namespace App\Enums;

enum ExpertApplicationStatus: string
{
    case Pending = 'pending';
    case NeedsCorrection = 'needs_correction';
    case Approved = 'approved';
    case Rejected = 'rejected';
}

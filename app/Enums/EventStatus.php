<?php

namespace App\Enums;

use App\Concerns\HasEnumOptions;

enum EventStatus: string
{
    use HasEnumOptions;

    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

}

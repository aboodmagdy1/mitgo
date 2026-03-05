<?php

namespace App\Enums;

enum TripRequestOutcome: int
{
    case PENDING = 0;
    case ACCEPTED = 1;
    case REJECTED = 2;
}

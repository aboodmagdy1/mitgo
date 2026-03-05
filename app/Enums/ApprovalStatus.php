<?php

namespace App\Enums;

enum ApprovalStatus: int
{
    case PENDING     = 0;
    case IN_PROGRESS = 1;
    case APPROVED    = 2;
    case REJECTED    = 3;

    public function label(): string
    {
        return match($this) {
            self::PENDING     => __('Pending'),
            self::IN_PROGRESS => __('In Progress'),
            self::APPROVED    => __('Approved'),
            self::REJECTED    => __('Rejected'),
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING     => 'warning',
            self::IN_PROGRESS => 'info',
            self::APPROVED    => 'success',
            self::REJECTED    => 'danger',
        };
    }
}

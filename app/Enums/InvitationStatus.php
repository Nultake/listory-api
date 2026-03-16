<?php

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending = "pending";
    case Accepted = "accepted";
    case Declined = "declined";
}

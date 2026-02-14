<?php

namespace Workbench\App\Enums;

enum PaymentStateEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
}

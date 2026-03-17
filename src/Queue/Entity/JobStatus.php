<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\Entity;

enum JobStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
}

<?php

namespace App\Enums;

enum TicketStatus: string
{
    case WAITING = 'WAITING';
    case CALLED = 'CALLED';
    case COMPLETED = 'COMPLETED';
    case DELETED = 'DELETED';

    /**
     * Status yang masih dianggap sebagai sesi aktif pengunjung.
     */
    public static function activeStatuses(): array
    {
        return [
            self::WAITING->value,
            self::CALLED->value,
        ];
    }
}
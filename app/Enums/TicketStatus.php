<?php
namespace App\Enums;

enum TicketStatus: string
{
    case WAITING = 'waiting';
    case CALLED = 'called';
    case COMPLETED = 'completed';
    case DELETED = 'deleted';

    public static function activeStatues(): array
    {
        return[
            self::WAITING->value,
            self::CALLED->value,
        ];
    }
}
?>
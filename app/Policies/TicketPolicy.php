<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Memastikan petugas hanya dapat mengelola tiket dari ruangan tugasnya.
     */
    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->room_id !== null
            && (int) $user->room_id === (int) $ticket->room_id;
    }
}

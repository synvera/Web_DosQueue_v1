<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Memastikan petugas hanya dapat mengelola tiket yang sesuai dengan
     * gelombang dan ruangan yang ditugaskan kepadanya.
     */
    public function manage(User $user, Ticket $ticket): bool
    {
        return $user->wave_id !== null
            && $user->room_id !== null
            && (int) $user->wave_id === (int) $ticket->wave_id
            && (int) $user->room_id === (int) $ticket->room_id;
    }
}
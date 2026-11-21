<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $fillable = [
        'token',
        'ticket_number',
        'wave_id',
        'room_id',
        'sequence_number',
        'status',
        'called_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'status' => TicketStatus::class,
            'called_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function wave(): BelongsTo
    {
        return $this->belongsTo(Wave::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [TicketStatus::WAITING, TicketStatus::CALLED], true);
    }
}
<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'token' => $this->token,
            'sequence_number' => $this->sequence_number,
            'status' => $this->status->value,
            'wave' => $this->whenLoaded('wave', fn () => [
                'id' => $this->wave->id,
                'name' => $this->wave->name,
                'code' => $this->wave->code,
            ]),
            'room' => $this->whenLoaded('room', fn () => [
                'id' => $this->room->id,
                'name' => $this->room->name,
                'code' => $this->room->code,
            ]),
            'called_at' => $this->called_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
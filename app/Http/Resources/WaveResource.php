<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WaveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // tickets_count diambil dari withCount('tickets') di controller
        $usedQuota = (int) ($this->tickets_count ?? $this->tickets()->count());
        $remainingQuota = max(0, $this->max_quota - $usedQuota);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_quota' => $this->max_quota,
            'used_quota' => $usedQuota,
            'remaining_quota' => $remainingQuota,
            'is_active' => $this->is_active,
            'is_available' => $this->is_active && $remainingQuota > 0,
        ];
    }
}
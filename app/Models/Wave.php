<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wave extends Model
{
    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'max_quota',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
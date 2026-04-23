<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'coverage_percent',
        'summary',
        'is_connected',
        'last_sync_at',
    ];

    protected function casts(): array
    {
        return [
            'is_connected' => 'boolean',
            'last_sync_at' => 'datetime',
        ];
    }
}

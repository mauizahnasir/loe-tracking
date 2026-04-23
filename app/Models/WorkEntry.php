<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'entry_date',
        'hours',
        'status',
        'confidence_score',
        'source',
        'note',
        'explanation',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function signals(): HasMany
    {
        return $this->hasMany(ActivitySignal::class);
    }
}

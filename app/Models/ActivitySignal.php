<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySignal extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_entry_id',
        'source',
        'label',
        'minutes',
    ];

    public function workEntry(): BelongsTo
    {
        return $this->belongsTo(WorkEntry::class);
    }
}

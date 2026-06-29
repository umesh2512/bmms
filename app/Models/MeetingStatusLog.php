<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['meeting_id', 'from_status', 'to_status', 'notes', 'changed_by'];

    protected $casts = ['created_at' => 'datetime'];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

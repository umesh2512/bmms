<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Minutes extends Model
{
    protected $table = 'minutes';

    protected $fillable = [
        'meeting_id', 'drafted_by', 'status', 'content',
        'approved_by', 'approved_at', 'locked_at', 'file_path',
    ];

    protected $casts = [
        'content'     => 'array',
        'approved_at' => 'datetime',
        'locked_at'   => 'datetime',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function draftedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'drafted_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked';
    }
}

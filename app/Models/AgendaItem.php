<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgendaItem extends Model
{
    protected $fillable = [
        'meeting_id', 'parent_id', 'order_column', 'title',
        'naac_criteria_no', 'hod_resolution_no', 'hod_resolution_date',
        'points_discussed', 'decisions', 'action_by', 'action_date',
        'description', 'presenter_id', 'time_allocated',
        'resolution_required', 'notes',
    ];

    protected $casts = [
        'resolution_required'  => 'boolean',
        'hod_resolution_date'  => 'date',
        'action_date'          => 'date',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order_column');
    }

    public function presenter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'presenter_id');
    }

    public function meetingDocuments(): HasMany
    {
        return $this->hasMany(MeetingDocument::class);
    }
}

<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActionItem extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'meeting_id', 'agenda_item_id', 'resolution_id',
        'title', 'description', 'assigned_to', 'assigned_by',
        'due_date', 'status', 'priority', 'completion_notes', 'completed_at',
    ];

    protected $casts = [
        'due_date'     => 'date',
        'completed_at' => 'datetime',
    ];

    public const STATUS_COLORS = [
        'open'        => 'info',
        'in_progress' => 'warning',
        'done'        => 'success',
        'cancelled'   => 'gray',
    ];

    public const PRIORITY_COLORS = [
        'low'    => 'gray',
        'medium' => 'warning',
        'high'   => 'danger',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function resolution(): BelongsTo
    {
        return $this->belongsTo(Resolution::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['done', 'cancelled'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString());
    }

    public function isOverdue(): bool
    {
        return ! in_array($this->status, ['done', 'cancelled'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status;
    }
}

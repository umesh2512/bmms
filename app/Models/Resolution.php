<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resolution extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id', 'meeting_id', 'agenda_item_id', 'title', 'body', 'type', 'status',
        'is_secret_ballot', 'required_majority', 'proposed_by', 'seconded_by',
        'votes_yes', 'votes_no', 'votes_abstain',
        'voting_opens_at', 'voting_closes_at', 'decided_at', 'result_notes',
    ];

    protected $casts = [
        'is_secret_ballot'  => 'boolean',
        'voting_opens_at'   => 'datetime',
        'voting_closes_at'  => 'datetime',
        'decided_at'        => 'datetime',
    ];

    public const STATUS_LABELS = [
        'proposed'  => 'Proposed',
        'voting'    => 'Voting Open',
        'passed'    => 'Passed',
        'failed'    => 'Failed',
        'withdrawn' => 'Withdrawn',
        'deferred'  => 'Deferred',
    ];

    public const MAJORITY_LABELS = [
        'simple'     => 'Simple Majority',
        'two_thirds' => 'Two-Thirds Majority',
        'unanimous'  => 'Unanimous',
    ];

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function agendaItem(): BelongsTo
    {
        return $this->belongsTo(AgendaItem::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function secondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seconded_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ResolutionVote::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function totalVotes(): int
    {
        return $this->votes_yes + $this->votes_no + $this->votes_abstain;
    }

    public function hasVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function isOpen(): bool
    {
        return $this->status === 'voting';
    }

    public function isDecided(): bool
    {
        return in_array($this->status, ['passed', 'failed', 'withdrawn', 'deferred']);
    }
}

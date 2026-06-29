<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Meeting extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'title', 'type', 'status',
        'department_id', 'committee_id', 'chairperson_id', 'secretary_id',
        'location', 'online_link', 'scheduled_date', 'start_time', 'end_time',
        'notice_days', 'quorum_required', 'quorum_count', 'notes', 'notice_sent_at',
    ];

    protected $casts = [
        'scheduled_date'  => 'date',
        'notice_sent_at'  => 'datetime',
        'quorum_required' => 'boolean',
    ];

    // Valid lifecycle transitions
    public const TRANSITIONS = [
        'draft'                  => ['scheduled'],
        'scheduled'              => ['agenda_prepared', 'draft'],
        'agenda_prepared'        => ['board_pack_generated', 'scheduled'],
        'board_pack_generated'   => ['rsvp_active', 'agenda_prepared'],
        'rsvp_active'            => ['in_progress', 'board_pack_generated'],
        'in_progress'            => ['minutes_drafted'],
        'minutes_drafted'        => ['minutes_under_approval', 'in_progress'],
        'minutes_under_approval' => ['closed', 'minutes_drafted'],
        'closed'                 => ['archived'],
        'archived'               => [],
    ];

    public const STATUS_LABELS = [
        'draft'                  => 'Draft',
        'scheduled'              => 'Scheduled',
        'agenda_prepared'        => 'Agenda Prepared',
        'board_pack_generated'   => 'Board Pack Generated',
        'rsvp_active'            => 'RSVP Active',
        'in_progress'            => 'In Progress',
        'minutes_drafted'        => 'Minutes Drafted',
        'minutes_under_approval' => 'Minutes Under Approval',
        'closed'                 => 'Closed',
        'archived'               => 'Archived',
    ];

    public function transitionTo(string $newStatus, ?string $notes = null): bool
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        if (! in_array($newStatus, $allowed)) {
            return false;
        }

        $old = $this->status;
        $this->update(['status' => $newStatus]);

        $this->statusLogs()->create([
            'from_status' => $old,
            'to_status'   => $newStatus,
            'notes'       => $notes,
            'changed_by'  => Auth::id(),
        ]);

        app(\App\Services\MeetingNotificationService::class)->onTransition($this, $newStatus);

        return true;
    }

    public function canTransitionTo(string $newStatus): bool
    {
        return in_array($newStatus, self::TRANSITIONS[$this->status] ?? []);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function chairperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'chairperson_id');
    }

    public function secretary(): BelongsTo
    {
        return $this->belongsTo(User::class, 'secretary_id');
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(MeetingAttendee::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(MeetingGuest::class);
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class)->orderBy('order_column');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(MeetingStatusLog::class);
    }

    public function meetingDocuments(): HasMany
    {
        return $this->hasMany(MeetingDocument::class)->orderBy('order_column');
    }

    public function boardPacks(): HasMany
    {
        return $this->hasMany(BoardPack::class);
    }

    public function latestBoardPack(): HasOne
    {
        return $this->hasOne(BoardPack::class)->latestOfMany();
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(Resolution::class);
    }

    public function actionItems(): HasMany
    {
        return $this->hasMany(ActionItem::class);
    }

    public function minutes(): HasOne
    {
        return $this->hasOne(Minutes::class);
    }
}

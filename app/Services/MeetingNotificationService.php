<?php

namespace App\Services;

use App\Mail\MeetingNotificationMail;
use App\Models\BmsNotification;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class MeetingNotificationService
{
    // Transitions that trigger notifications, with title/body factories and recipient strategy.
    private const CONFIG = [
        'scheduled' => [
            'title' => 'Meeting Scheduled: %s',
            'body'  => 'The meeting "%s" has been scheduled for %s.',
            'recipients' => 'attendees',
        ],
        'agenda_prepared' => [
            'title' => 'Agenda Ready: %s',
            'body'  => 'The agenda for "%s" has been published. Please review before the meeting on %s.',
            'recipients' => 'attendees',
        ],
        'board_pack_generated' => [
            'title' => 'Board Pack Available: %s',
            'body'  => 'The board pack for "%s" is ready for download. The meeting is on %s.',
            'recipients' => 'attendees',
        ],
        'rsvp_active' => [
            'title' => 'RSVP Required: %s',
            'body'  => 'Please confirm your attendance for "%s" scheduled on %s.',
            'recipients' => 'pending_rsvp',
        ],
        'in_progress' => [
            'title' => 'Meeting In Progress: %s',
            'body'  => 'The meeting "%s" has now started.',
            'recipients' => 'attendees',
        ],
        'minutes_under_approval' => [
            'title' => 'Minutes Awaiting Approval: %s',
            'body'  => 'The minutes for "%s" have been drafted and require your approval.',
            'recipients' => 'chair_and_secretary',
        ],
    ];

    public function onTransition(Meeting $meeting, string $newStatus): void
    {
        $config = self::CONFIG[$newStatus] ?? null;
        if (! $config) {
            return;
        }

        $meeting->loadMissing(['attendees.user', 'chairperson', 'secretary']);

        $recipients = $this->resolveRecipients($meeting, $config['recipients']);
        if ($recipients->isEmpty()) {
            return;
        }

        $date  = $meeting->scheduled_date?->format('d M Y') ?? 'TBC';
        $title = sprintf($config['title'], $meeting->title);
        $body  = sprintf($config['body'], $meeting->title, $date);

        foreach ($recipients as $user) {
            BmsNotification::notify(
                tenantId: $meeting->tenant_id,
                userId:   $user->id,
                type:     "meeting.{$newStatus}",
                title:    $title,
                body:     $body,
                data:     ['meeting_id' => $meeting->id],
            );

            Mail::to($user->email)
                ->queue(new MeetingNotificationMail($meeting, $user, $newStatus, $title, $body));
        }
    }

    private function resolveRecipients(Meeting $meeting, string $strategy): Collection
    {
        return match ($strategy) {
            'attendees' => $meeting->attendees
                ->map(fn ($a) => $a->user)
                ->filter(),

            'pending_rsvp' => $meeting->attendees
                ->filter(fn ($a) => ($a->rsvp_status ?? 'pending') === 'pending')
                ->map(fn ($a) => $a->user)
                ->filter(),

            'chair_and_secretary' => collect([
                $meeting->chairperson,
                $meeting->secretary,
            ])->filter()->unique('id'),

            default => collect(),
        };
    }
}

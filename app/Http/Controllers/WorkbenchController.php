<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendee;
use App\Models\BmsNotification;
use Illuminate\Support\Facades\Auth;

class WorkbenchController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        $upcomingMeetings = Meeting::withoutGlobalScope('tenant')
            ->where('tenant_id', $user->tenant_id)
            ->whereHas('attendees', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['scheduled', 'agenda_prepared', 'board_pack_generated', 'rsvp_active'])
            ->where('scheduled_date', '>=', now()->toDateString())
            ->orderBy('scheduled_date')
            ->limit(10)
            ->get();

        $pendingRsvps = MeetingAttendee::with('meeting')
            ->where('user_id', $user->id)
            ->where('rsvp_status', 'pending')
            ->whereHas('meeting', fn ($q) => $q
                ->where('tenant_id', $user->tenant_id)
                ->where('status', 'rsvp_active')
                ->where('scheduled_date', '>=', now()->toDateString()))
            ->get();

        $recentMeetings = Meeting::withoutGlobalScope('tenant')
            ->where('tenant_id', $user->tenant_id)
            ->whereHas('attendees', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('status', ['closed', 'archived', 'in_progress'])
            ->orderByDesc('scheduled_date')
            ->limit(5)
            ->get();

        $notifications = BmsNotification::withoutGlobalScope('tenant')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('workbench', compact(
            'upcomingMeetings',
            'pendingRsvps',
            'recentMeetings',
            'notifications',
        ));
    }
}

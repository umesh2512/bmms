<?php

namespace App\Http\Controllers;

use App\Models\MeetingAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RsvpController extends Controller
{
    public function respond(Request $request, MeetingAttendee $attendee)
    {
        $request->validate(['response' => 'required|in:yes,no,maybe,excused']);

        if ($attendee->user_id !== Auth::id()) {
            abort(403);
        }

        $attendee->update(['rsvp_status' => $request->response]);

        return back()->with('success', 'RSVP response recorded.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Resolution;
use App\Models\User;
use App\Services\ResolutionService;
use Illuminate\Http\Request;

class CircularResolutionController extends Controller
{
    public function show(Request $request, Resolution $resolution)
    {
        abort_unless($request->hasValidSignature(), 403, 'This voting link is invalid or has expired.');

        $user = User::findOrFail($request->query('user'));

        // If vote is pre-selected via email button link
        $preVote    = $request->query('vote');
        $alreadyVoted = $resolution->hasVoted($user->id);

        if ($preVote && in_array($preVote, ['yes', 'no', 'abstain']) && ! $alreadyVoted && $resolution->isOpen()) {
            try {
                app(ResolutionService::class)->castVote($resolution, $user, $preVote);
                $resolution->refresh();
                return view('circular-resolution.vote', compact('resolution', 'user'))
                    ->with('voted', $preVote);
            } catch (\RuntimeException $e) {
                // Fall through to show vote form with error
            }
        }

        return view('circular-resolution.vote', compact('resolution', 'user', 'alreadyVoted'));
    }

    public function vote(Request $request, Resolution $resolution)
    {
        abort_unless($request->hasValidSignature(), 403, 'This voting link is invalid or has expired.');

        $user = User::findOrFail($request->query('user'));
        $request->validate(['vote' => 'required|in:yes,no,abstain']);

        try {
            app(ResolutionService::class)->castVote($resolution, $user, $request->vote);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['vote' => $e->getMessage()]);
        }

        $resolution->refresh();

        return redirect()
            ->route('circular-resolution.show', ['resolution' => $resolution->id, 'user' => $user->id])
            ->with('voted', $request->vote);
    }
}

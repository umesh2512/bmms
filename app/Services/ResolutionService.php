<?php

namespace App\Services;

use App\Mail\CircularResolutionMail;
use App\Models\Resolution;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class ResolutionService
{
    public function castVote(Resolution $resolution, User $user, string $vote): void
    {
        if (! $resolution->isOpen()) {
            throw new \RuntimeException('Voting is not open for this resolution.');
        }

        if ($resolution->hasVoted($user->id)) {
            throw new \RuntimeException('You have already voted on this resolution.');
        }

        $resolution->votes()->create([
            'user_id' => $user->id,
            'vote'    => $vote,
        ]);

        $resolution->increment("votes_{$vote}");
    }

    public function openVoting(Resolution $resolution): void
    {
        $resolution->update([
            'status'           => 'voting',
            'voting_opens_at'  => now(),
        ]);
    }

    public function closeVoting(Resolution $resolution): void
    {
        $total  = $resolution->totalVotes();
        $passed = match ($resolution->required_majority) {
            'simple'     => $resolution->votes_yes > $resolution->votes_no,
            'two_thirds' => $total > 0 && ($resolution->votes_yes / $total) >= (2 / 3),
            'unanimous'  => $resolution->votes_no === 0 && $resolution->votes_abstain === 0 && $resolution->votes_yes > 0,
            default      => false,
        };

        $resolution->update([
            'status'           => $passed ? 'passed' : 'failed',
            'voting_closes_at' => now(),
            'decided_at'       => now(),
        ]);
    }

    public function notifyCircularResolutionVoters(Resolution $resolution): void
    {
        // Notify all active board members (+ board secretary) in the tenant
        $voters = User::withoutGlobalScope('tenant')
            ->where('tenant_id', $resolution->tenant_id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', [
                'board_member', 'board_secretary', 'tenant_admin',
            ]))
            ->get();

        foreach ($voters as $voter) {
            $signedUrl = URL::signedRoute('circular-resolution.show', [
                'resolution' => $resolution->id,
                'user'       => $voter->id,
            ], now()->addDays(7));

            Mail::to($voter->email)->queue(new CircularResolutionMail($resolution, $voter, $signedUrl));
        }
    }
}

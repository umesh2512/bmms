<div style="padding: 16px;">
    <table style="width:100%; border-collapse:collapse; font-size:14px;">
        <thead>
            <tr style="background:#f4f6f8;">
                <th style="padding:8px 12px; text-align:left; border-bottom:1px solid #d8e0e8;">Member</th>
                <th style="padding:8px 12px; text-align:center; border-bottom:1px solid #d8e0e8;">Vote</th>
                <th style="padding:8px 12px; text-align:right; border-bottom:1px solid #d8e0e8;">Time</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resolution->votes as $vote)
                <tr style="border-bottom:1px solid #f0f0f0;">
                    <td style="padding:8px 12px;">{{ $vote->user?->name ?? '—' }}</td>
                    <td style="padding:8px 12px; text-align:center;">
                        @if($vote->vote === 'yes')
                            <span style="color:#238636; font-weight:bold;">✓ Yes</span>
                        @elseif($vote->vote === 'no')
                            <span style="color:#b42318; font-weight:bold;">✗ No</span>
                        @else
                            <span style="color:#647184;">— Abstain</span>
                        @endif
                    </td>
                    <td style="padding:8px 12px; text-align:right; color:#9aacb8; font-size:12px;">
                        {{ $vote->voted_at?->format('d M, g:i A') }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" style="padding:16px; text-align:center; color:#9aacb8;">No votes yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div style="margin-top:16px; padding:12px; background:#f4f6f8; border-radius:6px; font-size:13px; display:flex; gap:24px;">
        <span style="color:#238636;"><strong>{{ $resolution->votes_yes }}</strong> Yes</span>
        <span style="color:#b42318;"><strong>{{ $resolution->votes_no }}</strong> No</span>
        <span style="color:#647184;"><strong>{{ $resolution->votes_abstain }}</strong> Abstain</span>
        <span style="color:#17202a; margin-left:auto;"><strong>{{ $resolution->totalVotes() }}</strong> Total</span>
    </div>
</div>

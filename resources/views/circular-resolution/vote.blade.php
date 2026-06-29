<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Circular Resolution — Cast Your Vote</title>
    <style>
        * { box-sizing:border-box; margin:0; padding:0; }
        body { font-family:system-ui,Arial,sans-serif; background:#f4f6f8; color:#17202a; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { background:#fff; border-radius:10px; box-shadow:0 12px 30px rgba(23,32,42,.1); max-width:560px; width:100%; overflow:hidden; }
        .card-header { background:#1f6feb; padding:28px 32px; }
        .card-header .logo { font-size:18px; font-weight:bold; color:#fff; }
        .card-header .label { font-size:12px; color:rgba(255,255,255,.7); margin-top:4px; }
        .card-body { padding:32px; }
        h1 { font-size:20px; margin-bottom:6px; }
        .sub { font-size:14px; color:#647184; margin-bottom:24px; }
        .res-box { background:#f4f6f8; border-left:4px solid #1f6feb; padding:16px 20px; border-radius:4px; margin-bottom:24px; }
        .res-box .title { font-weight:bold; font-size:15px; margin-bottom:8px; }
        .res-box .text { font-size:13px; color:#333; line-height:1.6; }
        .res-box .meta { font-size:12px; color:#9aacb8; margin-top:8px; }
        .voted-banner { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:16px 20px; border-radius:6px; font-size:15px; font-weight:600; margin-bottom:20px; }
        .vote-form { display:flex; flex-direction:column; gap:12px; }
        .vote-btn { padding:14px 20px; border-radius:8px; border:2px solid transparent; font-size:15px; font-weight:600; cursor:pointer; width:100%; transition:.15s; }
        .btn-yes { background:#238636; border-color:#238636; color:#fff; }
        .btn-yes:hover { background:#1a6627; }
        .btn-no  { background:#b42318; border-color:#b42318; color:#fff; }
        .btn-no:hover { background:#8c1a11; }
        .btn-abs { background:#fff; border-color:#d8e0e8; color:#647184; }
        .btn-abs:hover { background:#f4f6f8; }
        .deadline { font-size:12px; color:#b7791f; background:#fef9c3; border:1px solid #d4b200; padding:8px 12px; border-radius:4px; margin-top:16px; }
        .card-footer { padding:16px 32px; background:#f4f6f8; font-size:12px; color:#9aacb8; border-top:1px solid #e8eef4; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div class="logo">BMMS</div>
        <div class="label">Circular Resolution — Secure Voting</div>
    </div>
    <div class="card-body">
        <h1>Cast Your Vote</h1>
        <p class="sub">Hello {{ $user->name }}, please review and cast your vote below.</p>

        <div class="res-box">
            <div class="title">{{ $resolution->title }}</div>
            @if($resolution->body)
                <div class="text">{{ $resolution->body }}</div>
            @endif
            <div class="meta">
                Required: {{ \App\Models\Resolution::MAJORITY_LABELS[$resolution->required_majority] ?? '' }}
                @if($resolution->voting_closes_at)
                    &nbsp;|&nbsp; Closes: {{ $resolution->voting_closes_at->format('d M Y, g:i A') }}
                @endif
            </div>
        </div>

        @if(session('voted'))
            <div class="voted-banner">
                ✓ Your vote (<strong>{{ strtoupper(session('voted')) }}</strong>) has been recorded. Thank you.
            </div>
            <p style="color:#647184; font-size:14px;">
                Votes so far — For: {{ $resolution->votes_yes }}, Against: {{ $resolution->votes_no }}, Abstain: {{ $resolution->votes_abstain }}
            </p>
        @elseif($alreadyVoted)
            <div class="voted-banner">
                ✓ You have already voted on this resolution.
            </div>
        @elseif(!$resolution->isOpen())
            <div style="background:#fee2e2; border:1px solid #fca5a5; color:#991b1b; padding:14px 18px; border-radius:6px; font-size:14px;">
                Voting is closed for this resolution. Result: <strong>{{ strtoupper($resolution->status) }}</strong>
            </div>
        @else
            @if($errors->any())
                <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:6px; margin-bottom:16px; font-size:14px;">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('circular-resolution.vote', ['resolution' => $resolution->id, 'user' => $user->id]) }}" class="vote-form">
                @csrf
                <button type="submit" name="vote" value="yes" class="vote-btn btn-yes">✓ Yes — In Favour</button>
                <button type="submit" name="vote" value="no"  class="vote-btn btn-no">✗ No — Against</button>
                <button type="submit" name="vote" value="abstain" class="vote-btn btn-abs">— Abstain</button>
            </form>

            @if($resolution->voting_closes_at)
                <div class="deadline">⏰ Voting closes {{ $resolution->voting_closes_at->diffForHumans() }}</div>
            @endif
        @endif
    </div>
    <div class="card-footer">
        This is a secure, single-use voting link for {{ $user->email }}.
        Do not share this link with others.
    </div>
</div>
</body>
</html>

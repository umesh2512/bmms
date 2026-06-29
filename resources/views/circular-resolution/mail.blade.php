<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Circular Resolution — Vote Required</title>
    <style>
        body  { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:0; color:#17202a; }
        .wrap { max-width:560px; margin:40px auto; background:#fff; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.08); overflow:hidden; }
        .hdr  { background:#1f6feb; padding:28px 36px; }
        .hdr .logo { font-size:20px; font-weight:bold; color:#fff; letter-spacing:.5px; }
        .hdr .tag  { font-size:12px; color:rgba(255,255,255,.75); margin-top:4px; }
        .body { padding:36px; }
        h1 { font-size:18px; margin:0 0 12px; color:#17202a; }
        p  { font-size:14px; line-height:1.65; color:#647184; margin:0 0 16px; }
        .resolution-box { background:#f4f6f8; border-left:4px solid #1f6feb; padding:16px 20px; border-radius:4px; margin:20px 0; }
        .resolution-box .res-title { font-weight:bold; font-size:15px; color:#17202a; margin-bottom:8px; }
        .resolution-box .res-text  { font-size:13px; color:#333; line-height:1.6; }
        .meta { font-size:12px; color:#9aacb8; margin-top:10px; }
        .btn-row { margin:24px 0; display:flex; gap:12px; }
        .btn { display:inline-block; padding:11px 22px; border-radius:6px; font-size:14px; font-weight:600; text-decoration:none; }
        .btn-yes { background:#238636; color:#fff !important; }
        .btn-no  { background:#b42318; color:#fff !important; }
        .btn-abs { background:#647184; color:#fff !important; }
        .footer { font-size:11px; color:#9aacb8; border-top:1px solid #e8eef4; padding:18px 36px; }
        .deadline { background:#fef9c3; border:1px solid #d4b200; color:#854d0e; padding:10px 14px; border-radius:4px; font-size:13px; margin-top:16px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="hdr">
        <div class="logo">BMMS</div>
        <div class="tag">Circular Resolution — Your Vote Is Required</div>
    </div>
    <div class="body">
        <h1>Resolution Requires Your Vote</h1>
        <p>Hello {{ $recipient->name }},</p>
        <p>The following circular resolution has been proposed and requires your vote. Please review the resolution text and cast your vote before the deadline.</p>

        <div class="resolution-box">
            <div class="res-title">{{ $resolution->title }}</div>
            @if($resolution->body)
                <div class="res-text">{{ $resolution->body }}</div>
            @endif
            <div class="meta">
                Type: {{ ucfirst($resolution->type) }} &nbsp;|&nbsp;
                Required: {{ \App\Models\Resolution::MAJORITY_LABELS[$resolution->required_majority] ?? '' }}
                @if($resolution->proposed_by) &nbsp;|&nbsp; Proposed by {{ $resolution->proposedBy?->name }} @endif
            </div>
        </div>

        @if($resolution->voting_closes_at)
            <div class="deadline">
                ⏰ <strong>Voting closes:</strong> {{ $resolution->voting_closes_at->format('l, d F Y \a\t g:i A') }}
            </div>
        @endif

        <div class="btn-row">
            <a href="{{ $voteUrl }}&vote=yes" class="btn btn-yes">✓ Vote Yes</a>
            <a href="{{ $voteUrl }}&vote=no" class="btn btn-no">✗ Vote No</a>
            <a href="{{ $voteUrl }}&vote=abstain" class="btn btn-abs">— Abstain</a>
        </div>

        <p style="font-size:12px; color:#9aacb8;">
            Or visit this secure link to cast your vote:<br>
            <a href="{{ $voteUrl }}" style="color:#1f6feb; word-break:break-all;">{{ $voteUrl }}</a>
        </p>
    </div>
    <div class="footer">
        This email was sent by the Board Meeting Management System (BMMS).
        This voting link is unique to you and expires {{ $resolution->voting_closes_at ? 'on ' . $resolution->voting_closes_at->format('d M Y') : 'in 7 days' }}.
    </div>
</div>
</body>
</html>

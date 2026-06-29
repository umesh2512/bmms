<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notificationTitle }}</title>
    <style>
        body  { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 0; color: #17202a; }
        .wrap { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
        .header { background: #087f83; padding: 28px 36px; }
        .header .logo { font-size: 20px; font-weight: bold; color: #fff; letter-spacing: .5px; }
        .body  { padding: 36px; }
        h1 { font-size: 18px; margin: 0 0 12px; color: #17202a; }
        p  { font-size: 14px; line-height: 1.65; color: #647184; margin: 0 0 16px; }
        .meta { background: #f4f6f8; border-radius: 6px; padding: 18px 20px; margin: 24px 0; }
        .meta table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 5px 0; font-size: 13px; }
        .meta td:first-child { color: #9aacb8; width: 110px; }
        .meta td:last-child { font-weight: 600; color: #17202a; }
        .btn { display: inline-block; margin: 8px 0 24px; padding: 11px 26px; background: #1f6feb; color: #fff !important; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; }
        .badge-teal   { background: #ccfbf1; color: #134e4a; }
        .badge-blue   { background: #dbeafe; color: #1e40af; }
        .badge-amber  { background: #fef9c3; color: #854d0e; }
        .badge-green  { background: #dcfce7; color: #166534; }
        .footer { font-size: 11px; color: #9aacb8; border-top: 1px solid #e8eef4; padding: 18px 36px; }
    </style>
</head>
<body>
<div class="wrap">

    <div class="header">
        <div class="logo">BMMS</div>
    </div>

    <div class="body">
        <h1>{{ $notificationTitle }}</h1>

        <p>Hello {{ $recipient->name }},</p>
        <p>{{ $notificationBody }}</p>

        <div class="meta">
            <table>
                <tr>
                    <td>Meeting</td>
                    <td>{{ $meeting->title }}</td>
                </tr>
                <tr>
                    <td>Type</td>
                    <td>
                        <span class="badge badge-blue">{{ strtoupper($meeting->type) }}</span>
                    </td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>{{ $meeting->scheduled_date?->format('l, d F Y') ?? 'TBC' }}</td>
                </tr>
                @if($meeting->start_time)
                <tr>
                    <td>Time</td>
                    <td>
                        {{ \Carbon\Carbon::parse($meeting->start_time)->format('g:i A') }}
                        @if($meeting->end_time)
                            – {{ \Carbon\Carbon::parse($meeting->end_time)->format('g:i A') }}
                        @endif
                    </td>
                </tr>
                @endif
                @if($meeting->location)
                <tr>
                    <td>Location</td>
                    <td>{{ $meeting->location }}</td>
                </tr>
                @endif
                <tr>
                    <td>Status</td>
                    <td>
                        <span class="badge badge-teal">
                            {{ \App\Models\Meeting::STATUS_LABELS[$meeting->status] ?? $meeting->status }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>

        @if($transitionType === 'rsvp_active')
            <p>Please respond to confirm whether you will attend this meeting.</p>
            <a href="{{ config('app.url') }}/workbench" class="btn">Respond to RSVP</a>
        @elseif($transitionType === 'board_pack_generated')
            <p>Log in to BMMS to download the board pack for this meeting.</p>
            <a href="{{ config('app.url') }}/workbench" class="btn">View Board Pack</a>
        @elseif($transitionType === 'minutes_under_approval')
            <p>Please log in to review and approve the meeting minutes.</p>
            <a href="{{ config('app.url') }}/manage" class="btn">Review Minutes</a>
        @else
            <a href="{{ config('app.url') }}/workbench" class="btn">View in BMMS</a>
        @endif

    </div>

    <div class="footer">
        This email was sent by the Board Meeting Management System (BMMS).
        If you were not expecting this notification, please contact your organisation's board secretary.
    </div>

</div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMMS — Workbench</title>
    <style>
        :root {
            --bg:#f4f6f8; --surface:#fff; --surface-2:#eef3f7; --ink:#17202a;
            --muted:#647184; --line:#d8e0e8; --nav:#18212b; --nav-soft:#263241;
            --blue:#1f6feb; --teal:#087f83; --green:#238636; --amber:#b7791f;
            --red:#b42318; --radius:8px; --shadow:0 12px 30px rgba(23,32,42,.08);
        }
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{font-family:system-ui,Arial,sans-serif;background:var(--bg);color:var(--ink);display:flex;min-height:100vh}

        /* Sidebar */
        .sidebar{width:220px;background:var(--nav);flex-shrink:0;display:flex;flex-direction:column;padding:0}
        .sidebar-logo{padding:20px 18px;font-size:18px;font-weight:700;color:#fff;letter-spacing:.5px;border-bottom:1px solid var(--nav-soft)}
        .sidebar nav{padding:12px 0}
        .sidebar nav a{display:flex;align-items:center;gap:10px;padding:9px 18px;color:#c9d4e0;text-decoration:none;font-size:14px;border-radius:0;transition:background .15s}
        .sidebar nav a:hover,.sidebar nav a.active{background:var(--nav-soft);color:#fff}
        .sidebar-footer{margin-top:auto;padding:16px 18px;border-top:1px solid var(--nav-soft)}
        .sidebar-footer a{color:#7a8ea4;font-size:13px;text-decoration:none}

        /* Main */
        .main{flex:1;overflow-y:auto}
        .topbar{background:var(--surface);border-bottom:1px solid var(--line);padding:14px 28px;display:flex;align-items:center;justify-content:space-between}
        .topbar h1{font-size:18px;font-weight:600}
        .topbar .user{font-size:13px;color:var(--muted)}
        .content{padding:28px;display:grid;grid-template-columns:1fr 1fr;gap:20px}
        .content.wide{grid-template-columns:1fr}

        /* Cards */
        .card{background:var(--surface);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden}
        .card-header{padding:16px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between}
        .card-header h2{font-size:15px;font-weight:600}
        .card-body{padding:0}

        /* Meeting rows */
        .meeting-row{padding:14px 20px;border-bottom:1px solid var(--line);display:flex;align-items:flex-start;gap:12px}
        .meeting-row:last-child{border-bottom:none}
        .meeting-date{width:44px;text-align:center;flex-shrink:0}
        .meeting-date .day{font-size:20px;font-weight:700;line-height:1;color:var(--blue)}
        .meeting-date .mon{font-size:11px;text-transform:uppercase;color:var(--muted)}
        .meeting-info{flex:1;min-width:0}
        .meeting-info .title{font-size:14px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .meeting-info .meta{font-size:12px;color:var(--muted);margin-top:3px}
        .badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600}
        .badge-blue{background:#dbeafe;color:#1e40af}
        .badge-green{background:#dcfce7;color:#166534}
        .badge-amber{background:#fef9c3;color:#854d0e}
        .badge-gray{background:#f1f5f9;color:#475569}
        .badge-red{background:#fee2e2;color:#991b1b}
        .badge-teal{background:#ccfbf1;color:#134e4a}

        /* RSVP rows */
        .rsvp-row{padding:14px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;gap:12px}
        .rsvp-row:last-child{border-bottom:none}
        .rsvp-btns{display:flex;gap:8px}
        .btn{padding:6px 14px;border-radius:6px;border:none;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
        .btn-green{background:var(--green);color:#fff}
        .btn-red{background:var(--red);color:#fff}
        .btn-amber{background:var(--amber);color:#fff}

        /* Notification */
        .notif-row{padding:12px 20px;border-bottom:1px solid var(--line);font-size:13px}
        .notif-row:last-child{border-bottom:none}
        .notif-title{font-weight:500}
        .notif-body{color:var(--muted);margin-top:2px}
        .notif-time{font-size:11px;color:var(--muted);margin-top:4px}

        .empty{padding:24px 20px;text-align:center;color:var(--muted);font-size:14px}

        @media(max-width:900px){.content{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">BMMS</div>
        <nav>
            <a href="{{ route('workbench') }}" class="active">🏠 Workbench</a>
            <a href="#">📅 Meetings</a>
            <a href="#">📄 Documents</a>
            <a href="#">🗳 Votes</a>
            <a href="#">✅ Action Items</a>
            <a href="#">📊 Analytics</a>
        </nav>
        <div class="sidebar-footer">
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout').submit();">Sign out</a>
            <form id="logout" method="POST" action="{{ route('logout') }}">@csrf</form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <h1>Workbench</h1>
            <span class="user">{{ Auth::user()->name }} · {{ Auth::user()->tenant->name ?? 'BMMS' }}</span>
        </div>

        <div class="content">

            {{-- Upcoming Meetings --}}
            <div class="card">
                <div class="card-header">
                    <h2>Upcoming Meetings</h2>
                    <span class="badge badge-blue">{{ $upcomingMeetings->count() }}</span>
                </div>
                <div class="card-body">
                    @forelse($upcomingMeetings as $meeting)
                        <div class="meeting-row">
                            <div class="meeting-date">
                                <div class="day">{{ $meeting->scheduled_date->format('d') }}</div>
                                <div class="mon">{{ $meeting->scheduled_date->format('M') }}</div>
                            </div>
                            <div class="meeting-info">
                                <div class="title">{{ $meeting->title }}</div>
                                <div class="meta">
                                    {{ $meeting->start_time ? \Carbon\Carbon::parse($meeting->start_time)->format('g:i A') : '' }}
                                    {{ $meeting->location ? '· ' . $meeting->location : '' }}
                                </div>
                                <div style="margin-top:6px">
                                    <span class="badge badge-gray">{{ strtoupper($meeting->type) }}</span>
                                    <span class="badge badge-blue" style="margin-left:4px">{{ \App\Models\Meeting::STATUS_LABELS[$meeting->status] ?? $meeting->status }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty">No upcoming meetings</div>
                    @endforelse
                </div>
            </div>

            {{-- Pending RSVPs --}}
            <div class="card">
                <div class="card-header">
                    <h2>Pending RSVPs</h2>
                    @if($pendingRsvps->count())
                        <span class="badge badge-amber">{{ $pendingRsvps->count() }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @forelse($pendingRsvps as $attendee)
                        <div class="rsvp-row">
                            <div>
                                <div style="font-size:14px;font-weight:500">{{ $attendee->meeting->title }}</div>
                                <div style="font-size:12px;color:var(--muted)">
                                    {{ $attendee->meeting->scheduled_date?->format('d M Y') }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('rsvp.respond', $attendee->id) }}">
                                @csrf
                                <div class="rsvp-btns">
                                    <button type="submit" name="response" value="yes" class="btn btn-green">Yes</button>
                                    <button type="submit" name="response" value="no" class="btn btn-red">No</button>
                                    <button type="submit" name="response" value="maybe" class="btn btn-amber">Maybe</button>
                                </div>
                            </form>
                        </div>
                    @empty
                        <div class="empty">No pending RSVPs</div>
                    @endforelse
                </div>
            </div>

            {{-- Notifications --}}
            <div class="card">
                <div class="card-header">
                    <h2>Notifications</h2>
                    @if($notifications->count())
                        <span class="badge badge-red">{{ $notifications->count() }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @forelse($notifications as $notif)
                        <div class="notif-row">
                            <div class="notif-title">{{ $notif->title }}</div>
                            @if($notif->body)
                                <div class="notif-body">{{ $notif->body }}</div>
                            @endif
                            <div class="notif-time">{{ $notif->created_at->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="empty">No new notifications</div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Meetings --}}
            <div class="card">
                <div class="card-header">
                    <h2>Recent Meetings</h2>
                </div>
                <div class="card-body">
                    @forelse($recentMeetings as $meeting)
                        <div class="meeting-row">
                            <div class="meeting-date">
                                <div class="day">{{ $meeting->scheduled_date?->format('d') }}</div>
                                <div class="mon">{{ $meeting->scheduled_date?->format('M') }}</div>
                            </div>
                            <div class="meeting-info">
                                <div class="title">{{ $meeting->title }}</div>
                                <div class="meta">{{ \App\Models\Meeting::STATUS_LABELS[$meeting->status] ?? $meeting->status }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="empty">No recent meetings</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11pt; color: #17202a; }

  /* ---- Cover page ---- */
  .cover {
    page-break-after: always;
    height: 100%;
    display: flex;
    flex-direction: column;
    padding: 0;
  }
  .cover-header {
    background: #087f83;
    color: #fff;
    padding: 48px 56px 36px;
  }
  .cover-header .org { font-size: 13pt; letter-spacing: 1px; opacity: .85; margin-bottom: 12px; }
  .cover-header .title { font-size: 26pt; font-weight: bold; line-height: 1.2; margin-bottom: 8px; }
  .cover-header .sub { font-size: 13pt; opacity: .9; }
  .cover-body { padding: 40px 56px; flex: 1; }
  .cover-meta { border-left: 4px solid #087f83; padding-left: 16px; margin-bottom: 32px; }
  .cover-meta table { width: 100%; border-collapse: collapse; }
  .cover-meta td { padding: 5px 0; font-size: 11pt; }
  .cover-meta td:first-child { color: #647184; width: 140px; }
  .cover-meta td:last-child { font-weight: 600; }
  .cover-badge {
    display: inline-block;
    background: #1f6feb;
    color: #fff;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 10pt;
    font-weight: bold;
    letter-spacing: .5px;
  }
  .cover-confidential {
    margin-top: 24px;
    border: 1px solid #e74c3c;
    color: #e74c3c;
    padding: 8px 16px;
    border-radius: 4px;
    font-size: 10pt;
    display: inline-block;
  }
  .cover-footer {
    background: #f4f6f8;
    border-top: 2px solid #087f83;
    padding: 16px 56px;
    font-size: 9pt;
    color: #647184;
    display: flex;
    justify-content: space-between;
  }

  /* ---- Inner pages ---- */
  .page { page-break-before: always; padding: 40px 48px; }
  h2 {
    font-size: 15pt;
    color: #087f83;
    border-bottom: 2px solid #087f83;
    padding-bottom: 6px;
    margin-bottom: 18px;
  }

  /* Agenda */
  .agenda-item { margin-bottom: 12px; padding-left: 12px; border-left: 3px solid #d8e0e8; }
  .agenda-item .num { font-weight: bold; color: #1f6feb; margin-right: 6px; }
  .agenda-item .item-title { font-weight: 600; }
  .agenda-item .item-meta { font-size: 9.5pt; color: #647184; margin-top: 2px; }

  /* Document index */
  table.doc-index {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
  }
  table.doc-index th {
    background: #eef3f7;
    padding: 7px 10px;
    text-align: left;
    font-size: 9.5pt;
    color: #647184;
    border-bottom: 1px solid #d8e0e8;
  }
  table.doc-index td {
    padding: 7px 10px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: top;
  }
  table.doc-index tr:last-child td { border-bottom: none; }
  .badge-type {
    display: inline-block;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 8.5pt;
    font-weight: bold;
    background: #dbeafe;
    color: #1e40af;
  }

  /* Attendees */
  table.attendees {
    width: 100%;
    border-collapse: collapse;
    font-size: 10pt;
  }
  table.attendees th {
    background: #eef3f7;
    padding: 7px 10px;
    text-align: left;
    font-size: 9.5pt;
    color: #647184;
    border-bottom: 1px solid #d8e0e8;
  }
  table.attendees td {
    padding: 7px 10px;
    border-bottom: 1px solid #f0f0f0;
  }

  /* Page numbers */
  .page-num {
    position: fixed;
    bottom: 16px;
    right: 40px;
    font-size: 8.5pt;
    color: #aaa;
  }
  .page-header-bar {
    border-bottom: 1px solid #d8e0e8;
    padding-bottom: 8px;
    margin-bottom: 24px;
    font-size: 9pt;
    color: #aaa;
    display: flex;
    justify-content: space-between;
  }
</style>
</head>
<body>

{{-- ===== COVER PAGE ===== --}}
<div class="cover">
  <div class="cover-header">
    <div class="org">{{ $meeting->tenant->name ?? 'BMMS' }}</div>
    <div class="title">{{ $meeting->title }}</div>
    <div class="sub">Board Pack — Version {{ $boardPack->version }}</div>
  </div>

  <div class="cover-body">
    <div class="cover-meta">
      <table>
        <tr>
          <td>Meeting Type</td>
          <td>{{ strtoupper($meeting->type) }}</td>
        </tr>
        <tr>
          <td>Date</td>
          <td>{{ $meeting->scheduled_date?->format('l, d F Y') }}</td>
        </tr>
        <tr>
          <td>Time</td>
          <td>
            @if($meeting->start_time)
              {{ \Carbon\Carbon::parse($meeting->start_time)->format('g:i A') }}
              @if($meeting->end_time) – {{ \Carbon\Carbon::parse($meeting->end_time)->format('g:i A') }} @endif
            @else
              TBC
            @endif
          </td>
        </tr>
        <tr>
          <td>Location</td>
          <td>{{ $meeting->location ?: 'TBC' }}</td>
        </tr>
        <tr>
          <td>Chairperson</td>
          <td>{{ $meeting->chairperson?->name ?: '—' }}</td>
        </tr>
        <tr>
          <td>Secretary</td>
          <td>{{ $meeting->secretary?->name ?: '—' }}</td>
        </tr>
        <tr>
          <td>Generated</td>
          <td>{{ $boardPack->generated_at?->format('d M Y, g:i A') }}</td>
        </tr>
        <tr>
          <td>Generated By</td>
          <td>{{ $boardPack->generatedBy?->name }}</td>
        </tr>
      </table>
    </div>

    <div>
      <span class="cover-badge">CONFIDENTIAL</span>
      &nbsp;
      <span class="cover-badge" style="background:#238636">{{ $documents->count() }} Documents</span>
      &nbsp;
      <span class="cover-badge" style="background:#6f42c1">{{ $agendaItems->count() }} Agenda Items</span>
    </div>

    <div class="cover-confidential" style="margin-top: 24px;">
      &#128274; This board pack is confidential and intended solely for the named recipients.
      Unauthorised disclosure, copying or distribution is strictly prohibited.
    </div>
  </div>

  <div class="cover-footer">
    <span>Generated by BMMS &mdash; Board Meeting Management System</span>
    <span>{{ now()->format('d M Y') }}</span>
  </div>
</div>

{{-- ===== AGENDA PAGE ===== --}}
<div class="page">
  <div class="page-header-bar">
    <span>{{ $meeting->title }}</span>
    <span>Board Pack v{{ $boardPack->version }}</span>
  </div>

  <h2>Agenda</h2>

  @forelse($agendaItems as $item)
    <div class="agenda-item">
      <span class="num">{{ $item->order_column }}.</span>
      <span class="item-title">{{ $item->title }}</span>
      <div class="item-meta">
        @if($item->presenter)Presented by: {{ $item->presenter->name }} &nbsp;|&nbsp; @endif
        @if($item->time_allocated) {{ $item->time_allocated }} min @endif
        @if($item->resolution_required) &nbsp;&#8226;&nbsp; Resolution required @endif
      </div>
      @if($item->description)
        <div style="font-size:9.5pt; color:#555; margin-top:4px">{{ $item->description }}</div>
      @endif
    </div>
  @empty
    <p style="color:#647184; font-style:italic">No agenda items recorded.</p>
  @endforelse
</div>

{{-- ===== DOCUMENT INDEX ===== --}}
<div class="page">
  <div class="page-header-bar">
    <span>{{ $meeting->title }}</span>
    <span>Board Pack v{{ $boardPack->version }}</span>
  </div>

  <h2>Document Index</h2>

  @if($documents->count())
    <table class="doc-index">
      <thead>
        <tr>
          <th style="width:36px">#</th>
          <th>Document</th>
          <th>Agenda Item</th>
          <th style="width:56px">Type</th>
          <th style="width:56px">Size</th>
        </tr>
      </thead>
      <tbody>
        @foreach($documents as $i => $md)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>
              {{ $md->document->name }}
              @if($md->document->is_confidential)
                <span style="color:#e74c3c; font-size:8pt"> &#128274;</span>
              @endif
            </td>
            <td style="color:#647184; font-size:9.5pt">{{ $md->agendaItem?->title ?: '—' }}</td>
            <td><span class="badge-type">{{ strtoupper($md->document->file_type ?? '?') }}</span></td>
            <td style="color:#647184">{{ $md->document->file_size_for_humans }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p style="color:#647184; font-style:italic">No published documents attached to this meeting.</p>
  @endif
</div>

{{-- ===== ATTENDEES ===== --}}
<div class="page">
  <div class="page-header-bar">
    <span>{{ $meeting->title }}</span>
    <span>Board Pack v{{ $boardPack->version }}</span>
  </div>

  <h2>Attendees</h2>

  @if($meeting->attendees->count())
    <table class="attendees">
      <thead>
        <tr>
          <th>Name</th>
          <th>Role</th>
          <th>RSVP</th>
        </tr>
      </thead>
      <tbody>
        @foreach($meeting->attendees as $att)
          <tr>
            <td>{{ $att->user?->name }}</td>
            <td style="color:#647184; font-size:9.5pt">{{ ucfirst(str_replace('_', ' ', $att->role ?? '')) }}</td>
            <td>
              @php
                $rsvpColor = match($att->rsvp_status ?? '') {
                    'yes' => '#238636', 'no' => '#b42318', 'maybe' => '#b7791f',
                    default => '#647184'
                };
              @endphp
              <span style="color:{{ $rsvpColor }}; font-weight:600; text-transform:uppercase; font-size:9pt">
                {{ $att->rsvp_status ?? 'Pending' }}
              </span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @if($meeting->guests->count())
      <h2 style="margin-top:28px">Guests</h2>
      <table class="attendees">
        <thead>
          <tr><th>Name</th><th>Organisation</th><th>Email</th></tr>
        </thead>
        <tbody>
          @foreach($meeting->guests as $g)
            <tr>
              <td>{{ $g->name }}</td>
              <td style="color:#647184">{{ $g->organisation ?? '—' }}</td>
              <td style="color:#647184; font-size:9.5pt">{{ $g->email ?? '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  @else
    <p style="color:#647184; font-style:italic">No attendees recorded.</p>
  @endif
</div>

</body>
</html>

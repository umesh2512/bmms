<?php

namespace App\Services;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    // ─── Tenant-scoped ───────────────────────────────────────────────────────

    public function tenantMeetingStats(int $tenantId): array
    {
        $base = Meeting::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        $now      = now();
        $yearStart = $now->copy()->startOfYear();

        return [
            'total'        => $base->count(),
            'this_year'    => (clone $base)->where('scheduled_date', '>=', $yearStart)->count(),
            'this_month'   => (clone $base)->whereYear('scheduled_date', $now->year)->whereMonth('scheduled_date', $now->month)->count(),
            'by_status'    => (clone $base)->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status')->toArray(),
            'by_type'      => (clone $base)->select('type', DB::raw('count(*) as total'))->groupBy('type')->pluck('total', 'type')->toArray(),
            'closed'       => (clone $base)->whereIn('status', ['closed', 'archived'])->count(),
            'in_progress'  => (clone $base)->where('status', 'in_progress')->count(),
        ];
    }

    public function tenantAttendanceStats(int $tenantId): array
    {
        $rows = DB::select("
            SELECT
                COUNT(ma.id)                                                       AS total_invited,
                SUM(ma.attendance_status = 'present')                              AS attended,
                SUM(ma.rsvp_status != 'pending')                                   AS rsvp_responded
            FROM meetings m
            LEFT JOIN meeting_attendees ma ON ma.meeting_id = m.id
            WHERE m.tenant_id = ? AND m.deleted_at IS NULL
        ", [$tenantId]);

        $r = $rows[0] ?? null;
        $invited   = (int) ($r?->total_invited ?? 0);
        $attended  = (int) ($r?->attended ?? 0);
        $responded = (int) ($r?->rsvp_responded ?? 0);

        return [
            'total_invited'      => $invited,
            'total_attended'     => $attended,
            'attendance_rate'    => $invited > 0 ? round(($attended / $invited) * 100, 1) : 0,
            'rsvp_response_rate' => $invited > 0 ? round(($responded / $invited) * 100, 1) : 0,
        ];
    }

    public function tenantResolutionStats(int $tenantId): array
    {
        $rows = Resolution::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $total  = array_sum($rows);
        $passed = $rows['passed'] ?? 0;

        return [
            'total'       => $total,
            'passed'      => $passed,
            'failed'      => $rows['failed'] ?? 0,
            'pending'     => ($rows['proposed'] ?? 0) + ($rows['voting'] ?? 0),
            'withdrawn'   => ($rows['withdrawn'] ?? 0) + ($rows['deferred'] ?? 0),
            'pass_rate'   => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
            'by_status'   => $rows,
        ];
    }

    public function tenantActionItemStats(int $tenantId): array
    {
        $base = ActionItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at');

        $total      = $base->count();
        $done       = (clone $base)->where('status', 'done')->count();
        $overdue    = (clone $base)->overdue()->count();
        $open       = (clone $base)->whereIn('status', ['open', 'in_progress'])->count();

        return [
            'total'           => $total,
            'open'            => $open,
            'done'            => $done,
            'overdue'         => $overdue,
            'cancelled'       => (clone $base)->where('status', 'cancelled')->count(),
            'completion_rate' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
        ];
    }

    public function tenantDocumentStats(int $tenantId): array
    {
        $row = DB::selectOne("
            SELECT COUNT(*) as total, COALESCE(SUM(file_size), 0) as total_size
            FROM documents
            WHERE tenant_id = ? AND deleted_at IS NULL
        ", [$tenantId]);

        $bytes = (int) ($row?->total_size ?? 0);

        return [
            'total'      => (int) ($row?->total ?? 0),
            'total_size' => $bytes,
            'size_human' => $this->formatBytes($bytes),
        ];
    }

    /** Meetings per month for the past N months (bar chart data). */
    public function tenantMeetingsByMonth(int $tenantId, int $months = 12): array
    {
        $rows = DB::select("
            SELECT DATE_FORMAT(scheduled_date, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM meetings
            WHERE tenant_id = ?
              AND scheduled_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              AND deleted_at IS NULL
            GROUP BY month
            ORDER BY month
        ", [$tenantId, $months]);

        $map = collect($rows)->pluck('cnt', 'month')->toArray();

        $labels = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key      = now()->subMonths($i)->format('Y-m');
            $label    = now()->subMonths($i)->format('M Y');
            $labels[] = $label;
            $data[]   = (int) ($map[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    /** Last N meetings' attendance rates (line chart data). */
    public function tenantAttendanceByMeeting(int $tenantId, int $limit = 10): array
    {
        $rows = DB::select("
            SELECT
                m.title,
                m.scheduled_date,
                COUNT(ma.id) AS invited,
                SUM(ma.attendance_status = 'present') AS present
            FROM meetings m
            LEFT JOIN meeting_attendees ma ON ma.meeting_id = m.id
            WHERE m.tenant_id = ?
              AND m.status IN ('in_progress','closed','archived')
              AND m.deleted_at IS NULL
            GROUP BY m.id
            ORDER BY m.scheduled_date DESC
            LIMIT ?
        ", [$tenantId, $limit]);

        $rows = array_reverse($rows);

        $labels = [];
        $data   = [];
        foreach ($rows as $r) {
            $labels[] = \Carbon\Carbon::parse($r->scheduled_date)->format('d M');
            $data[]   = $r->invited > 0 ? round(($r->present / $r->invited) * 100, 1) : 0;
        }

        return compact('labels', 'data');
    }

    // ─── Superadmin / platform-scoped ────────────────────────────────────────

    public function platformStats(): array
    {
        return [
            'tenants_total'   => Tenant::withTrashed()->count(),
            'tenants_active'  => Tenant::where('status', 'active')->count(),
            'tenants_trial'   => Tenant::where('status', 'trial')->count(),
            'users_total'     => User::withoutGlobalScope('tenant')->count(),
            'users_active'    => User::withoutGlobalScope('tenant')->where('status', 'active')->count(),
            'meetings_total'  => Meeting::withoutGlobalScope('tenant')->count(),
            'meetings_30days' => Meeting::withoutGlobalScope('tenant')
                ->where('created_at', '>=', now()->subDays(30))->count(),
        ];
    }

    /** New tenants per month for the past N months. */
    public function platformTenantGrowth(int $months = 12): array
    {
        $rows = DB::select("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM tenants
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month
        ", [$months]);

        $map = collect($rows)->pluck('cnt', 'month')->toArray();

        $labels = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key      = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->format('M Y');
            $data[]   = (int) ($map[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    /** Meetings created per month across all tenants. */
    public function platformMeetingsByMonth(int $months = 12): array
    {
        $rows = DB::select("
            SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
            FROM meetings
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
              AND deleted_at IS NULL
            GROUP BY month
            ORDER BY month
        ", [$months]);

        $map = collect($rows)->pluck('cnt', 'month')->toArray();

        $labels = [];
        $data   = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $key      = now()->subMonths($i)->format('Y-m');
            $labels[] = now()->subMonths($i)->format('M Y');
            $data[]   = (int) ($map[$key] ?? 0);
        }

        return compact('labels', 'data');
    }

    // ─── Per-meeting ─────────────────────────────────────────────────────────

    public function meetingStats(Meeting $meeting): array
    {
        $attendees = $meeting->attendees;
        $invited   = $attendees->count();
        $present   = $attendees->where('attendance_status', 'present')->count();
        $responded = $attendees->where('rsvp_status', '!=', 'pending')->count();

        $resolutions = $meeting->resolutions;
        $actions     = $meeting->actionItems;

        return [
            'invited'         => $invited,
            'present'         => $present,
            'attendance_rate' => $invited > 0 ? round(($present / $invited) * 100, 1) : 0,
            'rsvp_rate'       => $invited > 0 ? round(($responded / $invited) * 100, 1) : 0,
            'resolutions'     => $resolutions->count(),
            'passed'          => $resolutions->where('status', 'passed')->count(),
            'failed'          => $resolutions->where('status', 'failed')->count(),
            'actions_total'   => $actions->count(),
            'actions_open'    => $actions->whereIn('status', ['open', 'in_progress'])->count(),
            'actions_done'    => $actions->where('status', 'done')->count(),
            'documents'       => $meeting->meetingDocuments->count(),
        ];
    }

    // ─── Export data ─────────────────────────────────────────────────────────

    public function meetingsExportRows(int $tenantId): array
    {
        $meetings = Meeting::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['chairperson', 'secretary', 'attendees', 'resolutions', 'actionItems'])
            ->orderBy('scheduled_date')
            ->get();

        $header = ['Meeting', 'Type', 'Status', 'Date', 'Chairperson', 'Secretary',
                   'Invited', 'Present', 'Attendance %', 'Resolutions', 'Passed', 'Actions'];

        $rows = $meetings->map(fn (Meeting $m) => [
            $m->title,
            strtoupper($m->type),
            $m->status,
            $m->scheduled_date?->format('d M Y'),
            $m->chairperson?->name,
            $m->secretary?->name,
            $m->attendees->count(),
            $m->attendees->where('attendance_status', 'present')->count(),
            $m->attendees->count() > 0
                ? round($m->attendees->where('attendance_status', 'present')->count() / $m->attendees->count() * 100, 1) . '%'
                : '—',
            $m->resolutions->count(),
            $m->resolutions->where('status', 'passed')->count(),
            $m->actionItems->count(),
        ])->toArray();

        return [$header, ...$rows];
    }

    public function actionItemsExportRows(int $tenantId): array
    {
        $items = ActionItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['meeting', 'assignedTo', 'assignedBy'])
            ->orderBy('due_date')
            ->get();

        $header = ['Title', 'Meeting', 'Assigned To', 'Assigned By', 'Due Date',
                   'Priority', 'Status', 'Overdue', 'Completed At'];

        $rows = $items->map(fn (ActionItem $a) => [
            $a->title,
            $a->meeting?->title ?? '—',
            $a->assignedTo?->name ?? '—',
            $a->assignedBy?->name ?? '—',
            $a->due_date?->format('d M Y') ?? '—',
            ucfirst($a->priority),
            ucfirst(str_replace('_', ' ', $a->status)),
            $a->isOverdue() ? 'Yes' : 'No',
            $a->completed_at?->format('d M Y') ?? '—',
        ])->toArray();

        return [$header, ...$rows];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)       return "{$bytes} B";
        if ($bytes < 1048576)    return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1073741824) return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 1) . ' GB';
    }
}

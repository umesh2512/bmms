<?php

namespace App\Http\Controllers;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\Resolution;
use App\Services\AnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class AnalyticsExportController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics) {}

    public function meetingsCsv(): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $rows     = $this->analytics->meetingsExportRows($tenantId);

        $csv = collect($rows)->map(fn ($row) => implode(',', array_map(
            fn ($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
            $row,
        )))->implode("\n");

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="meetings-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    public function actionsCsv(): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $rows     = $this->analytics->actionItemsExportRows($tenantId);

        $csv = collect($rows)->map(fn ($row) => implode(',', array_map(
            fn ($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
            $row,
        )))->implode("\n");

        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="action-items-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    public function governancePdf(): \Illuminate\Http\Response
    {
        $user     = Auth::user();
        $tenantId = $user->tenant_id;
        $tenant   = $user->tenant;

        $meetings = Meeting::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['attendees', 'resolutions', 'actionItems'])
            ->orderBy('scheduled_date')
            ->get();

        $resolutions = Resolution::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with('meeting')
            ->orderBy('created_at')
            ->get();

        $actionItems = ActionItem::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->with(['meeting', 'assignedTo'])
            ->orderBy('due_date')
            ->get();

        $meetingStats    = $this->analytics->tenantMeetingStats($tenantId);
        $attendanceStats = $this->analytics->tenantAttendanceStats($tenantId);
        $resolutionStats = $this->analytics->tenantResolutionStats($tenantId);
        $actionStats     = $this->analytics->tenantActionItemStats($tenantId);
        $period          = 'All time through ' . now()->format('d M Y');

        $pdf = Pdf::loadView('pdf.governance-report', compact(
            'tenant', 'meetings', 'resolutions', 'actionItems',
            'meetingStats', 'attendanceStats', 'resolutionStats', 'actionStats',
            'period',
        ))->setPaper('a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="governance-report-' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }
}

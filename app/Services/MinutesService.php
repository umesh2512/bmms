<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Minutes;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class MinutesService
{
    public function generatePdf(Minutes $minutes): Minutes
    {
        $meeting = $minutes->meeting->loadMissing([
            'tenant',
            'chairperson',
            'secretary',
            'agendaItems' => fn ($q) => $q->orderBy('order_column')->with('presenter'),
            'attendees.user',
            'resolutions',
        ]);

        $pdf = Pdf::loadView('pdf.minutes', compact('minutes', 'meeting'))
            ->setPaper('a4', 'portrait');

        $dir  = 'minutes/' . $meeting->tenant_id;
        $path = "{$dir}/meeting-{$meeting->id}.pdf";

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($path, $pdf->output());

        $minutes->update([
            'file_path'   => $path,
            'status'      => 'locked',
            'locked_at'   => now(),
        ]);

        // Advance meeting to closed if it's in minutes_under_approval
        if ($meeting->status === 'minutes_under_approval') {
            $meeting->transitionTo('closed', 'Minutes approved and locked.');
        }

        return $minutes->fresh();
    }

    public function submitForReview(Minutes $minutes): void
    {
        $minutes->update(['status' => 'under_review']);

        $meeting = $minutes->meeting;
        if (in_array($meeting->status, ['in_progress', 'minutes_drafted'])) {
            $meeting->transitionTo('minutes_under_approval', 'Minutes submitted for approval.');
        }
    }

    public function approve(Minutes $minutes, int $approverId): Minutes
    {
        $minutes->update([
            'approved_by' => $approverId,
            'approved_at' => now(),
            'status'      => 'approved',
        ]);

        return $this->generatePdf($minutes);
    }
}

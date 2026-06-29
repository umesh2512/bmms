<?php

namespace App\Services;

use App\Models\BoardPack;
use App\Models\BoardPackItem;
use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BoardPackService
{
    public function generate(Meeting $meeting, int $userId): BoardPack
    {
        $meeting->loadMissing([
            'tenant',
            'chairperson',
            'secretary',
            'agendaItems' => fn ($q) => $q->orderBy('order_column')->with('presenter'),
            'attendees.user',
            'guests',
            'meetingDocuments' => fn ($q) => $q
                ->where('stage', 'published')
                ->orderBy('order_column')
                ->with(['document', 'agendaItem']),
        ]);

        $version = $meeting->boardPacks()->count() + 1;

        $boardPack = $meeting->boardPacks()->create([
            'version'      => $version,
            'status'       => 'draft',
            'generated_by' => $userId,
            'generated_at' => now(),
        ]);

        // Create board pack items from published meeting documents
        $order = 1;
        foreach ($meeting->meetingDocuments as $md) {
            BoardPackItem::create([
                'board_pack_id'       => $boardPack->id,
                'meeting_document_id' => $md->id,
                'order_column'        => $order++,
            ]);
        }

        // Generate cover + TOC PDF
        $filePath = $this->renderPdf($boardPack, $meeting);
        $boardPack->update(['file_path' => $filePath]);

        return $boardPack->fresh(['generatedBy', 'items']);
    }

    public function publish(BoardPack $boardPack): void
    {
        $boardPack->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        // Auto-advance meeting from agenda_prepared → board_pack_generated
        $meeting = $boardPack->meeting;
        if ($meeting->status === 'agenda_prepared') {
            $meeting->transitionTo('board_pack_generated', 'Board pack published automatically.');
        }
    }

    public function streamZip(BoardPack $boardPack): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $boardPack->loadMissing(['items.meetingDocument.document', 'meeting']);

        $meetingTitle = \Illuminate\Support\Str::slug($boardPack->meeting->title);
        $zipName      = "board-pack-v{$boardPack->version}-{$meetingTitle}.zip";

        return response()->streamDownload(function () use ($boardPack) {
            $tmp = tempnam(sys_get_temp_dir(), 'bp_');

            $zip = new ZipArchive();
            $zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE);

            // Cover PDF
            if ($boardPack->file_path && Storage::disk('local')->exists($boardPack->file_path)) {
                $zip->addFromString('00_Board_Pack_Cover.pdf', Storage::disk('local')->get($boardPack->file_path));
            }

            // Numbered documents
            foreach ($boardPack->items->sortBy('order_column') as $item) {
                $doc = $item->meetingDocument?->document;
                if (! $doc || ! Storage::disk('local')->exists($doc->file_path)) {
                    continue;
                }
                $num      = str_pad($item->order_column, 2, '0', STR_PAD_LEFT);
                $safeName = \Illuminate\Support\Str::slug($doc->name);
                $ext      = strtolower($doc->file_type ?: pathinfo($doc->file_path, PATHINFO_EXTENSION));
                $zip->addFromString("{$num}_{$safeName}.{$ext}", Storage::disk('local')->get($doc->file_path));
            }

            $zip->close();

            readfile($tmp);
            @unlink($tmp);
        }, $zipName, [
            'Content-Type'        => 'application/zip',
            'Content-Disposition' => "attachment; filename=\"{$zipName}\"",
        ]);
    }

    private function renderPdf(BoardPack $boardPack, Meeting $meeting): string
    {
        $documents   = $meeting->meetingDocuments;
        $agendaItems = $meeting->agendaItems;

        $pdf = Pdf::loadView('pdf.board-pack-cover', compact('boardPack', 'meeting', 'documents', 'agendaItems'))
            ->setPaper('a4', 'portrait');

        $dir  = 'board_packs/' . $meeting->tenant_id;
        $name = "meeting-{$meeting->id}-v{$boardPack->version}.pdf";
        $path = "{$dir}/{$name}";

        Storage::disk('local')->makeDirectory($dir);
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}

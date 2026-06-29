<?php

namespace App\Http\Controllers;

use App\Models\BoardPack;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class BoardPackController extends Controller
{
    public function downloadPdf(BoardPack $boardPack)
    {
        $this->authorise($boardPack);

        abort_unless($boardPack->file_path && Storage::disk('local')->exists($boardPack->file_path), 404, 'Board pack file not found.');

        $meeting  = $boardPack->meeting;
        $filename = 'board-pack-v' . $boardPack->version . '-' . \Illuminate\Support\Str::slug($meeting->title) . '.pdf';

        return Storage::disk('local')->download($boardPack->file_path, $filename);
    }

    public function downloadZip(BoardPack $boardPack)
    {
        $this->authorise($boardPack);

        return app(\App\Services\BoardPackService::class)->streamZip($boardPack);
    }

    private function authorise(BoardPack $boardPack): void
    {
        $tenantId = $boardPack->meeting->tenant_id ?? null;
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403);
        }
    }
}

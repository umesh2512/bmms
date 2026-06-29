<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
        $this->authorise($document->tenant_id);

        abort_unless(Storage::disk('local')->exists($document->file_path), 404, 'File not found.');

        $filename = $document->name . '.' . $document->file_type;

        return Storage::disk('local')->download($document->file_path, $filename);
    }

    public function downloadVersion(DocumentVersion $version): StreamedResponse
    {
        $this->authorise($version->document->tenant_id);

        abort_unless(Storage::disk('local')->exists($version->file_path), 404, 'File not found.');

        $doc      = $version->document;
        $filename = $doc->name . '_v' . $version->version_number . '.' . pathinfo($version->file_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($version->file_path, $filename);
    }

    private function authorise(int $tenantId): void
    {
        if (Auth::user()->tenant_id !== $tenantId) {
            abort(403);
        }
    }
}

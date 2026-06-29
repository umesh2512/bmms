<?php

namespace App\Http\Controllers;

use App\Models\Minutes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinutesController extends Controller
{
    public function download(Minutes $minutes)
    {
        $meeting = $minutes->meeting;
        if (Auth::user()->tenant_id !== $meeting->tenant_id) {
            abort(403);
        }

        abort_unless($minutes->file_path && Storage::disk('local')->exists($minutes->file_path), 404, 'Minutes PDF not found.');

        $filename = 'minutes-' . Str::slug($meeting->title) . '.pdf';

        return Storage::disk('local')->download($minutes->file_path, $filename);
    }
}

<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [\App\Http\Controllers\WorkbenchController::class, '__invoke'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/workbench', \App\Http\Controllers\WorkbenchController::class)->name('workbench');
    Route::post('/rsvp/{attendee}', [\App\Http\Controllers\RsvpController::class, 'respond'])->name('rsvp.respond');
    Route::get('/documents/{document}/download', [\App\Http\Controllers\DocumentDownloadController::class, 'download'])->name('documents.download');
    Route::get('/documents/versions/{version}/download', [\App\Http\Controllers\DocumentDownloadController::class, 'downloadVersion'])->name('documents.version.download');
    Route::get('/board-packs/{boardPack}/pdf', [\App\Http\Controllers\BoardPackController::class, 'downloadPdf'])->name('board-packs.download-pdf');
    Route::get('/board-packs/{boardPack}/zip', [\App\Http\Controllers\BoardPackController::class, 'downloadZip'])->name('board-packs.download-zip');
    Route::get('/minutes/{minutes}/download', [\App\Http\Controllers\MinutesController::class, 'download'])->name('minutes.download');
    Route::get('/analytics/export/meetings.csv', [\App\Http\Controllers\AnalyticsExportController::class, 'meetingsCsv'])->name('analytics.export.meetings.csv');
    Route::get('/analytics/export/actions.csv', [\App\Http\Controllers\AnalyticsExportController::class, 'actionsCsv'])->name('analytics.export.actions.csv');
    Route::get('/analytics/export/governance-report.pdf', [\App\Http\Controllers\AnalyticsExportController::class, 'governancePdf'])->name('analytics.export.governance-pdf');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/invite/{token}', [\App\Http\Controllers\InvitationController::class, 'show'])->name('invite.show');
Route::post('/invite/{token}', [\App\Http\Controllers\InvitationController::class, 'accept'])->name('invite.accept');

// Signed routes for circular resolution voting (no login required — signed URL = auth)
Route::get('/vote/resolution/{resolution}', [\App\Http\Controllers\CircularResolutionController::class, 'show'])->name('circular-resolution.show');
Route::post('/vote/resolution/{resolution}', [\App\Http\Controllers\CircularResolutionController::class, 'vote'])->name('circular-resolution.vote');

require __DIR__.'/auth.php';

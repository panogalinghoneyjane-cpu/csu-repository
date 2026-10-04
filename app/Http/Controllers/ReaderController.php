<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Resource;
use App\Services\ResourceRecommender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Controlled-access viewing (no downloading):
 *  1. Files live in PRIVATE storage (storage/app/private) - there is no public link to them.
 *  2. The reader page gets a SIGNED link that expires after 10 minutes and only works while logged in.
 *  3. The file is only sent to the in-page PDF viewer; opening the link directly in a browser tab is blocked.
 *  4. The viewer draws pages as images (canvas) - no download/print/save buttons, right-click disabled.
 *  5. Every page shows a watermark with the reader's name, ID number and date/time.
 *  6. Every view is recorded in access_logs (who, what, when) for monitoring.
 */
class ReaderController extends Controller
{
    public function show(Request $request, Resource $resource, ResourceRecommender $recommender)
    {
        abort_if($resource->status !== 'active' && ! $request->user()->isLibrarian(), 404);

        AccessLog::create([
            'user_id' => $request->user()->id,
            'resource_id' => $resource->id,
            'accessed_at' => now(),
        ]);

        $resource->load('subject.program.college');

        return view('reader.show', [
            'resource' => $resource,
            'streamUrl' => URL::temporarySignedRoute('reader.stream', now()->addMinutes(10), ['resource' => $resource->id]),
            'related' => $recommender->forResource($resource),
            'watermark' => $request->user()->name.' · '.$request->user()->id_number.' · '.now()->format('M d, Y h:i A'),
        ]);
    }

    public function stream(Request $request, Resource $resource)
    {
        abort_if($resource->status !== 'active' && ! $request->user()->isLibrarian(), 404);

        // Block typing/pasting the link into the address bar (browser sends Sec-Fetch-Dest: document).
        abort_if(in_array($request->header('Sec-Fetch-Dest'), ['document', 'iframe', 'embed', 'object'], true), 403, 'Direct file access is not allowed. Please use the reader.');

        $disk = Storage::disk('local');
        abort_unless($disk->exists($resource->file_path), 404, 'File not found.');

        return response()->file($disk->path($resource->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="protected.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

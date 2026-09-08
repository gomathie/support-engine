<?php

namespace App\Http\Controllers;

use App\Models\LessonResource;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only route to a lesson file.
 *
 * Resources live on the `private` disk, which has no URL and no public
 * visibility, so there is no way to reach these bytes that skips the policy
 * check below. Paths are never taken from the request — only the resource id
 * is, and the path comes from the row.
 */
class ResourceDownloadController extends Controller
{
    public function download(LessonResource $resource): StreamedResponse
    {
        $this->authorize('download', $resource);

        return $this->serve($resource, 'attachment');
    }

    public function stream(LessonResource $resource): StreamedResponse
    {
        $this->authorize('stream', $resource);

        return $this->serve($resource, 'inline');
    }

    private function serve(LessonResource $resource, string $disposition): StreamedResponse
    {
        $disk = Storage::disk($resource->disk);

        abort_unless($disk->exists($resource->path), 404);

        $filename = $resource->original_filename ?: $resource->name;

        return $disk->response(
            $resource->path,
            $filename,
            [
                'Content-Type' => $resource->mime_type ?: 'application/octet-stream',

                // Inline rendering of an untrusted upload is a stored-XSS risk;
                // nosniff stops a browser deciding an "image" is really HTML.
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=0, no-store',
            ],
            $disposition,
        );
    }
}

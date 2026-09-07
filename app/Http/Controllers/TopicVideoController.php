<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Topic;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only route to an uploaded topic video.
 *
 * Videos live on the `private` disk — no URL, no public visibility — so there is
 * no way to reach the bytes that skips the policy below. The path is never taken
 * from the request; only the topic is, and the path comes from the row.
 *
 * Range requests matter here in a way they do not for a PDF: a browser seeking
 * in a <video> element sends `Range`, and a server that answers 200 with the
 * whole file makes the scrubber useless on anything large.
 */
class TopicVideoController extends Controller
{
    public function stream(Course $course, Topic $topic): Response
    {
        abort_unless($topic->course_id === $course->id, 404);

        // Same ability as reading the topic itself: if you may see the page,
        // you may see the video on it. Nothing else reaches this route.
        $this->authorize('view', $topic);

        abort_unless($topic->type->isUploadedVideo() && $topic->video_path, 404);

        $disk = Storage::disk($topic->video_disk ?: 'private');

        abort_unless($disk->exists($topic->video_path), 404);

        $headers = [
            'Content-Type' => $topic->video_mime_type ?: 'video/mp4',

            // An uploaded file rendered inline is a stored-XSS risk if a browser
            // decides the "video" is really HTML.
            'X-Content-Type-Options' => 'nosniff',

        ];

        /*
         * A local disk gives us a real path, and BinaryFileResponse handles
         * Range, 206, If-Range and multipart ranges properly — none of which is
         * worth reimplementing. Remote disks fall back to a progressive stream:
         * playback works, seeking past the buffer does not, and that is the
         * trade until the storage decision in PA-1 is made.
         */
        if ($this->isLocal($topic->video_disk ?: 'private')) {
            $response = response()
                ->file($disk->path($topic->video_path), $headers)
                ->setAutoLastModified()
                ->setAutoEtag();

            /*
             * Explicitly, and after the response is built: response()->file()
             * sets its own Cache-Control, and a `public` directive on
             * per-user-authorised content would let a shared proxy hand this
             * video to somebody the policy just refused.
             *
             * Revalidation rather than no-store, so a browser can still reuse
             * what it has while the viewer scrubs a 500 MB file.
             */
            $response->setPrivate();
            $response->headers->addCacheControlDirective('must-revalidate');
            $response->setMaxAge(0);

            return $response;
        }

        return $this->progressive($disk, $topic, $headers);
    }

    private function isLocal(string $disk): bool
    {
        return config("filesystems.disks.{$disk}.driver") === 'local';
    }

    /** @param  array<string, string>  $headers */
    private function progressive($disk, Topic $topic, array $headers): StreamedResponse
    {
        $headers['Content-Length'] = (string) $disk->size($topic->video_path);
        $headers['Accept-Ranges'] = 'none';
        $headers['Cache-Control'] = 'private, max-age=0, must-revalidate';

        return response()->stream(
            function () use ($disk, $topic): void {
                $stream = $disk->readStream($topic->video_path);

                if ($stream === null || $stream === false) {
                    return;
                }

                fpassthru($stream);
                fclose($stream);
            },
            BinaryFileResponse::HTTP_OK,
            $headers,
        );
    }
}

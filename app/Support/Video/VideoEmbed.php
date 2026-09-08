<?php

namespace App\Support\Video;

/**
 * A parsed video reference.
 *
 * Authors paste whatever URL the browser gave them. We never put that string in
 * an iframe src — a pasted URL is untrusted input, and `src` is a script-capable
 * sink. Instead the URL is parsed down to a provider and an id, both validated
 * against a strict pattern, and the embed URL is *rebuilt* from a fixed template.
 * Anything that does not parse is rejected at the form rather than rendered.
 *
 * The embed parameters are not cosmetic:
 *   - youtube-nocookie.com   no tracking cookie until the viewer presses play
 *   - rel=0                  suggestions restricted to the same channel, so the
 *                            end of a training video is not a doorway to
 *                            unrelated content
 *   - modestbranding=1       no YouTube logo in the control bar
 *   - dnt=1 (Vimeo)          the same do-not-track request
 */
final class VideoEmbed
{
    public const YOUTUBE = 'youtube';

    public const VIMEO = 'vimeo';

    private function __construct(
        public readonly string $provider,
        public readonly string $id,
    ) {}

    /**
     * Parse a pasted URL, or a bare id already stored on a lesson.
     *
     * Returns null rather than throwing: the caller is usually a validator or a
     * view, and both want "is this usable" rather than an exception.
     */
    public static function parse(?string $input, ?string $provider = null): ?self
    {
        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Already an id — the stored case, where the provider is known.
        if ($provider !== null && self::isValidId($provider, $input)) {
            return new self($provider, $input);
        }

        return self::fromYouTube($input) ?? self::fromVimeo($input);
    }

    /**
     * What may legally follow a video id: a query, a fragment, a further path
     * segment, or the end of the string.
     *
     * Without this the patterns match a prefix and quietly discard the rest, so
     * `…/ID" onload="alert(1)` would parse as a valid id. The rebuilt embed URL
     * would still be safe — the id is the only variable part and it is
     * character-constrained — but an author who pasted something malformed
     * should be told, not silently corrected.
     */
    private const ID_BOUNDARY = '(?=[?&/\#]|$)';

    private static function fromYouTube(string $url): ?self
    {
        // youtu.be/ID · youtube.com/watch?v=ID · /embed/ID · /shorts/ID · /v/ID
        $patterns = [
            '#^https?://(?:www\.)?youtu\.be/([A-Za-z0-9_-]{11})'.self::ID_BOUNDARY.'#',
            '#^https?://(?:www\.|m\.)?youtube(?:-nocookie)?\.com/watch\?(?:[^"\s]*&)?v=([A-Za-z0-9_-]{11})'.self::ID_BOUNDARY.'#',
            '#^https?://(?:www\.)?youtube(?:-nocookie)?\.com/(?:embed|v|shorts)/([A-Za-z0-9_-]{11})'.self::ID_BOUNDARY.'#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return new self(self::YOUTUBE, $matches[1]);
            }
        }

        return null;
    }

    private static function fromVimeo(string $url): ?self
    {
        $patterns = [
            '#^https?://(?:www\.)?vimeo\.com/(?:channels/[\w]+/)?(\d{6,12})'.self::ID_BOUNDARY.'#',
            '#^https?://player\.vimeo\.com/video/(\d{6,12})'.self::ID_BOUNDARY.'#',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches) === 1) {
                return new self(self::VIMEO, $matches[1]);
            }
        }

        return null;
    }

    public static function isValidId(string $provider, string $id): bool
    {
        return match ($provider) {
            self::YOUTUBE => preg_match('/^[A-Za-z0-9_-]{11}$/', $id) === 1,
            self::VIMEO => preg_match('/^\d{6,12}$/', $id) === 1,
            default => false,
        };
    }

    /** The URL that goes in the iframe. Built from a template, never from input. */
    public function embedUrl(): string
    {
        return match ($this->provider) {
            self::YOUTUBE => 'https://www.youtube-nocookie.com/embed/'.$this->id
                .'?rel=0&modestbranding=1&playsinline=1',
            self::VIMEO => 'https://player.vimeo.com/video/'.$this->id
                .'?dnt=1&title=0&byline=0&portrait=0',
        };
    }

    /** Where the author should go to edit or check the source. */
    public function canonicalUrl(): string
    {
        return match ($this->provider) {
            self::YOUTUBE => 'https://www.youtube.com/watch?v='.$this->id,
            self::VIMEO => 'https://vimeo.com/'.$this->id,
        };
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            self::YOUTUBE => 'YouTube',
            self::VIMEO => 'Vimeo',
        };
    }

    /** @return array{provider: string, id: string, embed_url: string, canonical_url: string, provider_label: string} */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'id' => $this->id,
            'embed_url' => $this->embedUrl(),
            'canonical_url' => $this->canonicalUrl(),
            'provider_label' => $this->providerLabel(),
        ];
    }
}

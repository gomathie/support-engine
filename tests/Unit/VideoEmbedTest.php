<?php

namespace Tests\Unit;

use App\Support\Video\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * URL parsing for video lessons (PA-9).
 *
 * This is the security boundary of the feature: an author pastes a URL, and the
 * only thing standing between that string and an iframe `src` is this parser.
 * Every rejection case below is a real attempt at getting arbitrary content
 * into that attribute.
 */
class VideoEmbedTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function youtubeUrls(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch without www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'watch with extra params' => ['https://www.youtube.com/watch?t=30&v=dQw4w9WgXcQ&feature=share', 'dQw4w9WgXcQ'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ?t=42', 'dQw4w9WgXcQ'],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'nocookie embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'dQw4w9WgXcQ'],
            'with whitespace' => ['  https://youtu.be/dQw4w9WgXcQ  ', 'dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('youtubeUrls')]
    public function test_it_parses_every_shape_of_youtube_url(string $url, string $expectedId): void
    {
        $embed = VideoEmbed::parse($url);

        $this->assertNotNull($embed, $url.' should parse');
        $this->assertSame(VideoEmbed::YOUTUBE, $embed->provider);
        $this->assertSame($expectedId, $embed->id);
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function vimeoUrls(): array
    {
        return [
            'plain' => ['https://vimeo.com/347119375', '347119375'],
            'with hash' => ['https://vimeo.com/347119375/abc123', '347119375'],
            'channel' => ['https://vimeo.com/channels/staffpicks/347119375', '347119375'],
            'player' => ['https://player.vimeo.com/video/347119375', '347119375'],
        ];
    }

    #[DataProvider('vimeoUrls')]
    public function test_it_parses_vimeo_urls(string $url, string $expectedId): void
    {
        $embed = VideoEmbed::parse($url);

        $this->assertNotNull($embed, $url.' should parse');
        $this->assertSame(VideoEmbed::VIMEO, $embed->provider);
        $this->assertSame($expectedId, $embed->id);
    }

    /** @return array<string, array{0: ?string}> */
    public static function rejectedInput(): array
    {
        return [
            'empty' => [''],
            'null' => [null],
            'whitespace' => ['   '],
            'not a url' => ['dQw4w9WgXcQ'],
            'unknown host' => ['https://example.com/watch?v=dQw4w9WgXcQ'],

            // The host must be youtube.com itself, not a subdomain of an
            // attacker's domain that merely contains the string.
            'lookalike host' => ['https://youtube.com.evil.test/watch?v=dQw4w9WgXcQ'],
            'suffix host' => ['https://notyoutube.com/watch?v=dQw4w9WgXcQ'],

            // The attribute this feeds is a script-capable sink.
            'javascript scheme' => ['javascript:alert(1)'],
            'data uri' => ['data:text/html,<script>alert(1)</script>'],

            // Trailing junk after an otherwise valid id. The rebuilt URL would
            // be safe either way, but a prefix match that silently discarded
            // the rest would hide the author's mistake.
            'quote break-out' => ['https://youtu.be/dQw4w9WgXcQ" onload="alert(1)'],
            'trailing text' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQZZZ'],

            'short id' => ['https://youtu.be/tooShort'],
            'vimeo non numeric' => ['https://vimeo.com/notanid'],
        ];
    }

    #[DataProvider('rejectedInput')]
    public function test_it_rejects_anything_it_does_not_recognise(?string $input): void
    {
        $this->assertNull(VideoEmbed::parse($input));
    }

    /**
     * The guarantee behind all of the above: whatever survives parsing produces
     * an embed URL built from a fixed template, so the id is the only variable
     * part and it is already constrained to a safe character set.
     */
    public function test_the_embed_url_is_privacy_preserving_and_template_built(): void
    {
        $youtube = VideoEmbed::parse('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        $this->assertStringStartsWith('https://www.youtube-nocookie.com/embed/', $youtube->embedUrl());
        $this->assertStringContainsString('rel=0', $youtube->embedUrl());
        $this->assertStringNotContainsString('"', $youtube->embedUrl());

        $vimeo = VideoEmbed::parse('https://vimeo.com/347119375');

        $this->assertStringStartsWith('https://player.vimeo.com/video/', $vimeo->embedUrl());
        $this->assertStringContainsString('dnt=1', $vimeo->embedUrl());
    }

    /** The stored case: a bare id plus a known provider, no URL involved. */
    public function test_it_parses_a_stored_id_against_its_provider(): void
    {
        $embed = VideoEmbed::parse('dQw4w9WgXcQ', VideoEmbed::YOUTUBE);

        $this->assertNotNull($embed);
        $this->assertSame('dQw4w9WgXcQ', $embed->id);

        // A Vimeo id is numeric; a YouTube id is not interchangeable with it.
        $this->assertNull(VideoEmbed::parse('dQw4w9WgXcQ', VideoEmbed::VIMEO));
        $this->assertNull(VideoEmbed::parse('anything', 'tiktok'));
    }

    public function test_the_canonical_url_points_back_at_the_source(): void
    {
        $this->assertSame(
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            VideoEmbed::parse('https://youtu.be/dQw4w9WgXcQ')->canonicalUrl(),
        );

        $this->assertSame(
            'https://vimeo.com/347119375',
            VideoEmbed::parse('https://player.vimeo.com/video/347119375')->canonicalUrl(),
        );
    }
}

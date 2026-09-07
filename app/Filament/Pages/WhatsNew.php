<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders CHANGELOG.md, split by month.
 *
 * The markdown file is the single source of truth — this screen reads it rather
 * than holding its own copy, so there is no second place to update and no way
 * for the two to drift. Adding a `## <Month> <Year>` heading to the file adds a
 * section here.
 */
class WhatsNew extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'What\'s new';

    protected static ?string $title = 'What\'s new';

    // Ungrouped and sorted last: this is a meta page about the product, not
    // another place to administer it.
    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.whats-new';

    public static function changelogPath(): string
    {
        return base_path('CHANGELOG.md');
    }

    /**
     * The changelog, split into one entry per month.
     *
     * @return array<int, array{heading: string, body: string}>
     */
    public function releases(): array
    {
        $path = static::changelogPath();

        if (! File::exists($path)) {
            return [];
        }

        // Keyed on the file's modification time, so an edit shows immediately
        // while repeated views do not re-read and re-parse it.
        return Cache::remember(
            'whats-new:'.File::lastModified($path),
            now()->addDay(),
            fn () => $this->parse(File::get($path)),
        );
    }

    /** @return array<int, array{heading: string, body: string}> */
    private function parse(string $markdown): array
    {
        // HTML comments carry authoring notes for whoever edits the file; they
        // are not for this screen.
        $markdown = preg_replace('/<!--.*?-->/s', '', $markdown) ?? $markdown;

        /*
         * Split on level-two headings, keeping the heading with its body.
         * PREG_SPLIT_DELIM_CAPTURE returns [preamble, heading, body, heading,
         * body, …]; the preamble is the file's title and intro, which the page
         * header already covers.
         */
        $parts = preg_split(
            '/^##\s+(.+?)\s*$/m',
            $markdown,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );

        if ($parts === false || count($parts) < 3) {
            return [];
        }

        $releases = [];

        for ($i = 1; $i < count($parts); $i += 2) {
            $body = trim($parts[$i + 1] ?? '');

            // A trailing `---` separates months in the file and would render as
            // a stray rule at the foot of a card.
            $body = trim(preg_replace('/\n---\s*$/', '', $body) ?? $body);

            if ($body === '') {
                continue;
            }

            $releases[] = [
                'heading' => trim($parts[$i]),
                'body' => $this->toHtml($body),
            ];
        }

        return $releases;
    }

    /**
     * The file lives in the repository and is written by whoever ships the
     * change, so it is not untrusted input — but raw HTML is stripped and unsafe
     * links refused anyway. Nothing in a changelog needs either, and the cost of
     * being wrong about who can edit the file is high.
     */
    private function toHtml(string $markdown): string
    {
        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }
}

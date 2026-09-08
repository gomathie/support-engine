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
     * Overall changelog metrics across all parsed releases.
     *
     * @return array{
     *     total_releases: int,
     *     latest_release: ?string,
     *     total_items: int,
     *     total_added: int,
     *     total_changed: int,
     *     total_fixed: int,
     *     total_limitations: int,
     * }
     */
    public function summary(): array
    {
        $releases = $this->releases();

        $totalAdded = 0;
        $totalChanged = 0;
        $totalFixed = 0;
        $totalLimitations = 0;
        $totalItems = 0;

        foreach ($releases as $release) {
            $stats = $release['stats'] ?? [];
            $totalAdded += $stats['added'] ?? 0;
            $totalChanged += $stats['changed'] ?? 0;
            $totalFixed += $stats['fixed'] ?? 0;
            $totalLimitations += $stats['limitations'] ?? 0;
            $totalItems += $stats['total'] ?? 0;
        }

        return [
            'total_releases' => count($releases),
            'latest_release' => $releases[0]['heading'] ?? null,
            'total_items' => $totalItems,
            'total_added' => $totalAdded,
            'total_changed' => $totalChanged,
            'total_fixed' => $totalFixed,
            'total_limitations' => $totalLimitations,
        ];
    }

    /**
     * The changelog, split into one entry per month.
     *
     * @return array<int, array{
     *     id: string,
     *     heading: string,
     *     body: string,
     *     sections: array<int, array{
     *         title: string,
     *         type: string,
     *         color: string,
     *         raw_html: string,
     *         items: array<int, array{
     *             title: string,
     *             description: string,
     *             html: string,
     *             search_text: string,
     *         }>,
     *     }>,
     *     stats: array{
     *         added: int,
     *         changed: int,
     *         fixed: int,
     *         limitations: int,
     *         other: int,
     *         total: int,
     *     },
     * }>
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

    /** @return array<int, array<string, mixed>> */
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
            $heading = trim($parts[$i]);
            $body = trim($parts[$i + 1] ?? '');

            // A trailing `---` separates months in the file and would render as
            // a stray rule at the foot of a card.
            $body = trim(preg_replace('/\n---\s*$/', '', $body) ?? $body);

            if ($body === '') {
                continue;
            }

            $parsedSections = $this->parseSections($body);

            $stats = [
                'added' => 0,
                'changed' => 0,
                'fixed' => 0,
                'limitations' => 0,
                'other' => 0,
                'total' => 0,
            ];

            foreach ($parsedSections as $sec) {
                $type = $sec['type'];
                $count = count($sec['items']);
                if (isset($stats[$type])) {
                    $stats[$type] += $count;
                } else {
                    $stats['other'] += $count;
                }
                $stats['total'] += $count;
            }

            $releases[] = [
                'id' => Str::slug($heading),
                'heading' => $heading,
                'body' => $this->toHtml($body),
                'sections' => $parsedSections,
                'stats' => $stats,
            ];
        }

        return $releases;
    }

    /**
     * Parses level-three sections (### Added, ### Changed, etc.) and their bullet items.
     *
     * @return array<int, array{
     *     title: string,
     *     type: string,
     *     color: string,
     *     raw_html: string,
     *     items: array<int, array{
     *         title: string,
     *         description: string,
     *         html: string,
     *         search_text: string,
     *     }>,
     * }>
     */
    private function parseSections(string $markdownBody): array
    {
        $sections = preg_split(
            '/^###\s+(.+?)\s*$/m',
            $markdownBody,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );

        if ($sections === false || count($sections) < 3) {
            return [
                [
                    'title' => 'Updates',
                    'type' => 'other',
                    'color' => 'gray',
                    'raw_html' => $this->toHtml($markdownBody),
                    'items' => $this->parseItems($markdownBody),
                ],
            ];
        }

        $result = [];

        for ($s = 1; $s < count($sections); $s += 2) {
            $secTitle = trim($sections[$s]);
            $secContent = trim($sections[$s + 1] ?? '');

            $type = match (strtolower($secTitle)) {
                'added' => 'added',
                'changed' => 'changed',
                'fixed' => 'fixed',
                'known limitations', 'limitations', 'known issues' => 'limitations',
                default => 'other',
            };

            $color = match ($type) {
                'added' => 'success',
                'changed' => 'info',
                'fixed' => 'warning',
                'limitations' => 'danger',
                default => 'gray',
            };

            $items = $this->parseItems($secContent);

            $result[] = [
                'title' => $secTitle,
                'type' => $type,
                'color' => $color,
                'raw_html' => $this->toHtml($secContent),
                'items' => $items,
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{
     *     title: string,
     *     description: string,
     *     html: string,
     *     search_text: string,
     * }>
     */
    private function parseItems(string $markdown): array
    {
        $lines = explode("\n", $markdown);
        $rawItems = [];
        $current = '';

        foreach ($lines as $line) {
            if (preg_match('/^[-*]\s+(.*)$/', $line, $matches)) {
                if ($current !== '') {
                    $rawItems[] = trim($current);
                }
                $current = $matches[1];
            } else {
                if ($current !== '') {
                    $current .= "\n" . $line;
                }
            }
        }

        if ($current !== '') {
            $rawItems[] = trim($current);
        }

        $items = [];
        foreach ($rawItems as $raw) {
            $leadTitle = '';
            $bodyText = $raw;

            if (preg_match('/^\*\*(.+?)\*\*[:.]?\s*(.*)$/s', $raw, $m)) {
                $leadTitle = trim($m[1], " \t\n\r\0\x0B.");
                $bodyText = trim($m[2]);
            }

            $html = $this->toHtml($raw);
            $searchText = strtolower(strip_tags($html));

            $items[] = [
                'title' => $leadTitle,
                'description' => $bodyText,
                'html' => $html,
                'search_text' => $searchText,
            ];
        }

        return $items;
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

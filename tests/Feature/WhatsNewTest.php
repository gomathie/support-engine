<?php

namespace Tests\Feature;

use App\Filament\Pages\WhatsNew;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The "What's new" screen.
 *
 * It reads CHANGELOG.md rather than holding its own copy, so the tests are
 * mostly about that file staying in a shape the page can parse — a heading
 * convention is easy to break silently.
 */
class WhatsNewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The page caches on the file's mtime; a stale entry from another test
        // run would mask a parsing change.
        Cache::flush();
    }

    public function test_the_changelog_exists_where_the_page_looks_for_it(): void
    {
        $this->assertTrue(
            File::exists(WhatsNew::changelogPath()),
            'CHANGELOG.md is the source for the What\'s new screen.',
        );
    }

    public function test_it_splits_the_changelog_into_months_newest_first(): void
    {
        $releases = (new WhatsNew())->releases();

        $this->assertNotEmpty($releases, 'The changelog should yield at least one month.');

        foreach ($releases as $release) {
            $this->assertArrayHasKey('heading', $release);
            $this->assertArrayHasKey('body', $release);
            $this->assertNotSame('', trim($release['body']));

            // "September 2026" — a month and a year, which is what the page
            // renders as its section title.
            $this->assertMatchesRegularExpression(
                '/^(January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4}$/',
                $release['heading'],
                'Month headings drive the sections; keep the "## Month Year" shape.',
            );
        }
    }

    /** The authoring notes in the file are for whoever edits it, not for readers. */
    public function test_html_comments_are_not_rendered(): void
    {
        $releases = (new WhatsNew())->releases();

        foreach ($releases as $release) {
            $this->assertStringNotContainsString('<!--', $release['body']);
            $this->assertStringNotContainsString('single source of truth', $release['body']);
        }
    }

    public function test_the_page_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin())
            ->get(WhatsNew::getUrl())
            ->assertSuccessful()
            ->assertSee('September 2026')
            ->assertSee('Latest');
    }

    /** Trainers use the platform too, and should know what changed in it. */
    public function test_the_page_renders_for_a_trainer(): void
    {
        $this->actingAs($this->trainer())
            ->get(WhatsNew::getUrl())
            ->assertSuccessful();
    }

    public function test_a_trainee_cannot_reach_the_admin_panel_at_all(): void
    {
        $this->actingAs($this->trainee())
            ->get(WhatsNew::getUrl())
            ->assertForbidden();
    }

    public function test_it_is_labelled_whats_new_in_the_sidebar(): void
    {
        $this->assertSame('What\'s new', WhatsNew::getNavigationLabel());
    }

    /** A missing file should be an empty screen, not a 500. */
    public function test_a_missing_changelog_yields_no_sections(): void
    {
        File::shouldReceive('exists')->once()->andReturnFalse();

        $this->assertSame([], (new WhatsNew())->releases());
    }
}

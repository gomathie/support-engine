<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin panel must compile its own stylesheet.
 *
 * Filament ships a *precompiled* CSS file built from Filament's own source. A
 * Tailwind utility used only in this application's Blade views is not in it, so
 * a custom page written in `rounded-2xl` and `bg-emerald-50` renders unstyled
 * while the source looks perfectly correct — nothing errors, nothing warns, and
 * the page simply arrives naked.
 *
 * That is exactly what had happened: 215 of the 257 classes on the What's new
 * page, and 41 of 61 on the KPI dashboard, did not exist in the stylesheet the
 * panel was loading.
 *
 * The fix is a custom theme that re-runs Tailwind over the panel's views. These
 * assertions hold the three pieces of it together, because removing any one of
 * them silently unstyles every custom page again.
 */
class AdminThemeTest extends TestCase
{
    use RefreshDatabase;

    private const THEME = 'resources/css/filament/admin/theme.css';

    public function test_the_theme_stylesheet_exists(): void
    {
        $this->assertFileExists(base_path(self::THEME));
    }

    /** Without this, `npm run build` never emits the file. */
    public function test_vite_builds_the_theme(): void
    {
        $this->assertStringContainsString(
            self::THEME,
            file_get_contents(base_path('vite.config.js')),
            'vite.config.js must list the admin theme as an input.',
        );
    }

    /** Without this, the panel keeps loading Filament\'s precompiled CSS. */
    public function test_the_panel_registers_the_theme(): void
    {
        $this->assertStringContainsString(
            "viteTheme('".self::THEME."')",
            file_get_contents(base_path('app/Providers/Filament/AdminPanelProvider.php')),
            'AdminPanelProvider must point ->viteTheme() at the admin theme.',
        );
    }

    /**
     * The whole point. Filament's own theme entry opens with
     * `@import 'tailwindcss' source(none)`, which switches automatic file
     * detection off — so without an explicit `@source` for our views, the build
     * succeeds and produces a stylesheet that knows nothing about our markup.
     */
    public function test_the_theme_scans_the_panels_own_views(): void
    {
        $css = file_get_contents(base_path(self::THEME));

        $this->assertStringContainsString(
            'resources/views/filament/**/*.blade.php',
            $css,
            'The theme must scan the panel\'s Blade views, or their classes compile to nothing.',
        );

        $this->assertStringContainsString(
            'app/Filament/**/*.php',
            $css,
            'Resource and page classes carry Tailwind classes too.',
        );
    }

    /**
     * Filament defines no `x-cloak` rule of its own, so an element that starts
     * hidden is visible until Alpine boots. The What's new page relies on it.
     */
    public function test_x_cloak_is_defined(): void
    {
        $this->assertMatchesRegularExpression(
            '/\[x-cloak\]\s*\{[^}]*display:\s*none/',
            file_get_contents(base_path(self::THEME)),
        );
    }
}

<script setup>
/**
 * Chrome modelled on academy.pilot-gps.com: a 16-unit white bar with a hairline
 * bottom border, the gradient logo tile, quiet slate nav links that turn brand
 * blue on hover, and a max-w-6xl content column.
 *
 * Flat and opaque on purpose — the translucent, blurred version this replaced
 * was hard to read against scrolling content.
 */
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';
import NotificationBell from '@/Components/NotificationBell.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const navOpen = ref(false);

const links = [
    { label: 'Dashboard', route: 'dashboard', match: /^\/dashboard/ },
    { label: 'Courses', route: 'courses.index', match: /^\/courses/ },
    { label: 'My Progress', route: 'progress.index', match: /^\/my-progress/ },
    { label: 'Support Panel', route: 'support-panel.index', match: /^\/support-panel/ },
    { label: 'Certificates', route: 'certificates.index', match: /^\/certificates/ },
];

const isActive = (link) => link.match.test(page.url);

watch(() => page.url, () => (navOpen.value = false));

const theme = ref(document.documentElement.getAttribute('data-theme') || 'light');

function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-theme', theme.value);
    localStorage.setItem('pilot_theme', theme.value);

    router.put(
        route('profile.theme'),
        { theme: theme.value },
        { preserveScroll: true, preserveState: true, only: [] },
    );
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-canvas">
        <!-- ─── HEADER ──────────────────────────────────────── -->
        <header class="sticky top-0 z-20 border-b border-line bg-nav">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-5">
                <!--
                    The PILOT lockup already contains the wordmark, so the
                    product name sits beside it behind a divider rather than
                    repeating the brand. Two files rather than a CSS filter:
                    the light logo is navy-on-transparent and disappears against
                    a dark header.
                -->
                <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-3 no-underline">
                    <img
                        src="/images/logo.png"
                        alt="PILOT"
                        class="h-7 w-auto dark:hidden"
                    />
                    <img
                        src="/images/logo-white.png"
                        alt="PILOT"
                        class="hidden h-7 w-auto dark:block"
                    />

                    <span class="hidden h-6 w-px bg-line sm:block"></span>

                    <span class="hidden text-base leading-tight font-bold text-navy sm:block">
                        Support Training Hub
                    </span>
                </Link>

                <!-- Desktop nav -->
                <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex">
                    <Link
                        v-for="link in links"
                        :key="link.route"
                        :href="route(link.route)"
                        class="rounded-lg px-3 py-2 text-sm font-medium no-underline transition-colors"
                        :class="
                            isActive(link)
                                ? 'bg-brand-soft text-brand'
                                : 'text-ink-sec hover:bg-nav-hover hover:text-brand'
                        "
                    >
                        {{ link.label }}
                    </Link>
                </nav>

                <div class="flex items-center gap-2.5">
                    <NotificationBell />

                    <button
                        type="button"
                        class="hidden cursor-pointer rounded-lg p-2 text-ink-sec transition-colors hover:bg-nav-hover hover:text-brand sm:block"
                        :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                        @click="toggleTheme"
                    >
                        <svg v-if="theme === 'dark'" class="h-[18px] w-[18px] fill-current" viewBox="0 0 24 24">
                            <path
                                d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18.75a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-1.5a.75.75 0 01.75-.75zM6.166 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.06 1.061l1.59 1.59zM4.5 12a.75.75 0 01-.75.75H1.5a.75.75 0 010-1.5h2.25a.75.75 0 01.75.75zM6.166 5.106a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 101.06-1.06l-1.59-1.591z"
                            />
                        </svg>
                        <svg v-else class="h-[18px] w-[18px] fill-current" viewBox="0 0 24 24">
                            <path
                                d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                            />
                        </svg>
                    </button>

                    <!--
                        A plain anchor, not an Inertia <Link>. The admin panel
                        is Filament — Blade and Livewire — not an Inertia page.
                        An Inertia Link would XHR it, get HTML where it expects
                        JSON, and render Filament inside Inertia's error modal,
                        where none of the menus work because they navigate the
                        iframe rather than the page. This needs a full page load.
                    -->
                    <a
                        v-if="user?.is_admin || user?.is_manager"
                        href="/admin"
                        class="hidden rounded-lg px-3 py-1.5 text-sm font-medium text-ink-sec no-underline transition-colors hover:bg-nav-hover hover:text-brand lg:block"
                    >
                        Admin
                    </a>

                    <!-- The gradient rather than bg-navy: navy is a light text
                         colour in dark mode, which left white initials on a
                         near-white circle. -->
                    <Link
                        :href="route('profile.edit')"
                        class="brand-gradient flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white no-underline"
                        :title="user?.name"
                    >
                        {{ user?.initials }}
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hidden cursor-pointer bg-transparent text-sm font-medium text-ink-sec transition-colors hover:text-brand sm:block"
                    >
                        Log out
                    </Link>

                    <button
                        type="button"
                        class="cursor-pointer rounded-lg p-2 text-ink-sec hover:bg-nav-hover lg:hidden"
                        aria-label="Toggle navigation"
                        @click="navOpen = !navOpen"
                    >
                        <svg class="h-5 w-5 fill-none stroke-current stroke-2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile nav -->
            <nav v-if="navOpen" class="border-t border-line bg-nav px-5 py-3 lg:hidden">
                <Link
                    v-for="link in links"
                    :key="link.route"
                    :href="route(link.route)"
                    class="block rounded-lg px-3 py-2.5 text-sm font-medium no-underline"
                    :class="isActive(link) ? 'bg-brand-soft text-brand' : 'text-ink-sec'"
                >
                    {{ link.label }}
                </Link>

                <!-- Plain anchor: Filament is not an Inertia page. -->
                <a
                    v-if="user?.is_admin || user?.is_manager"
                    href="/admin"
                    class="block rounded-lg px-3 py-2.5 text-sm font-medium text-ink-sec no-underline"
                >
                    Admin panel
                </a>

                <div class="mt-2 flex items-center gap-2 border-t border-line pt-2">
                    <button
                        type="button"
                        class="flex-1 cursor-pointer rounded-lg px-3 py-2.5 text-left text-sm font-medium text-ink-sec"
                        @click="toggleTheme"
                    >
                        {{ theme === 'dark' ? 'Light mode' : 'Dark mode' }}
                    </button>
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="cursor-pointer rounded-lg px-3 py-2.5 text-sm font-medium text-ink-sec"
                    >
                        Log out
                    </Link>
                </div>
            </nav>
        </header>

        <FlashMessages />

        <main class="mx-auto w-full max-w-6xl flex-1 px-5 py-8">
            <slot />
        </main>

        <footer class="mx-auto w-full max-w-6xl px-5 py-10 text-center text-sm text-ink-dis">
            Support Training Hub · PILOT platform onboarding
        </footer>
    </div>
</template>

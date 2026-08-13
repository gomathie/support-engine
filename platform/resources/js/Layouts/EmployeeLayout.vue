<script setup>
/**
 * The top navigation from the prototype, made into one component.
 *
 * In the prototype this markup was copy-pasted into all four HTML files, with
 * the `active` class hand-set per page and the theme/hamburger logic duplicated
 * in scripts/shared.js. Everything here is the same to the pixel — 54px bar,
 * blurred translucent background, mono uppercase links, 768px hamburger
 * breakpoint — but it exists once and derives its own active state.
 */
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashMessages from '@/Components/FlashMessages.vue';

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

// Close the mobile menu on navigation, matching the prototype's behaviour of
// collapsing when a link is tapped.
watch(() => page.url, () => (navOpen.value = false));

const theme = ref(document.documentElement.getAttribute('data-theme') || 'light');

function toggleTheme() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';

    // Applied immediately and mirrored to localStorage so the next full page
    // load has it before first paint; persisted to the account in the
    // background so it follows the employee to another device.
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
    <div class="min-h-screen bg-canvas">
        <!-- ─── SITE NAV ──────────────────────────────────── -->
        <nav
            class="sticky top-0 z-100 border-b border-line bg-nav backdrop-blur-[14px] transition-colors duration-300"
        >
            <div class="mx-auto flex h-[54px] max-w-[1200px] items-center gap-3 px-6">
                <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2.5 no-underline">
                    <img
                        src="/images/logo.png"
                        alt="PILOT"
                        class="h-6 w-auto brightness-0 invert-[0.2] dark:brightness-100 dark:invert-0"
                    />
                    <span class="mono-label text-[11px] tracking-[2.5px] whitespace-nowrap text-primary">
                        PILOT <b class="tracking-[1px] text-ink">Training Hub</b>
                    </span>
                </Link>

                <div class="flex-1"></div>

                <div
                    id="siteNavLinks"
                    class="flex items-center gap-1 max-md:absolute max-md:top-[54px] max-md:right-0 max-md:left-0 max-md:flex-col max-md:gap-1 max-md:border-b max-md:border-line max-md:bg-nav max-md:p-4 max-md:shadow-lg max-md:backdrop-blur-[14px]"
                    :class="navOpen ? 'max-md:flex' : 'max-md:hidden'"
                >
                    <Link
                        v-for="link in links"
                        :key="link.route"
                        :href="route(link.route)"
                        class="mono-label rounded-[5px] border border-transparent px-3 py-[7px] text-[11px] tracking-[1.2px] whitespace-nowrap no-underline transition-all duration-250"
                        :class="
                            isActive(link)
                                ? 'border-primary bg-primary font-bold text-on-accent'
                                : 'text-ink-sec hover:border-line hover:bg-nav-hover hover:text-ink'
                        "
                    >
                        {{ link.label }}
                    </Link>
                </div>

                <button
                    type="button"
                    class="flex cursor-pointer items-center justify-center rounded-[5px] border border-line px-2 py-1.5 text-ink-sec transition-all duration-250 hover:border-primary hover:text-primary"
                    :aria-label="theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'"
                    :title="theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                    @click="toggleTheme"
                >
                    <!-- Sun -->
                    <svg v-if="theme === 'dark'" class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M12 2.25a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0V3a.75.75 0 01.75-.75zM7.5 12a4.5 4.5 0 119 0 4.5 4.5 0 01-9 0zM18.894 6.166a.75.75 0 00-1.06-1.06l-1.591 1.59a.75.75 0 101.06 1.061l1.591-1.59zM21.75 12a.75.75 0 01-.75.75h-2.25a.75.75 0 010-1.5H21a.75.75 0 01.75.75zM17.834 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.061 1.06l1.59 1.591zM12 18.75a.75.75 0 01.75.75V21a.75.75 0 01-1.5 0v-1.5a.75.75 0 01.75-.75zM6.166 18.894a.75.75 0 001.06-1.06l-1.59-1.591a.75.75 0 10-1.06 1.061l1.59 1.59zM4.5 12a.75.75 0 01-.75.75H1.5a.75.75 0 010-1.5h2.25a.75.75 0 01.75.75zM6.166 5.106a.75.75 0 00-1.06 1.06l1.59 1.591a.75.75 0 101.06-1.06l-1.59-1.591z"
                        />
                    </svg>
                    <!-- Moon -->
                    <svg v-else class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                        <path
                            d="M9.528 1.718a.75.75 0 01.162.819A8.97 8.97 0 009 6a9 9 0 009 9 8.97 8.97 0 003.463-.69.75.75 0 01.981.98 10.503 10.503 0 01-9.694 6.46c-5.799 0-10.5-4.701-10.5-10.5 0-4.368 2.667-8.112 6.46-9.694a.75.75 0 01.818.162z"
                        />
                    </svg>
                </button>

                <!-- Account -->
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('profile.edit')"
                        class="mono-label hidden rounded-[5px] border border-line px-2.5 py-1.5 text-[10px] tracking-[1px] text-ink-sec no-underline transition-all duration-250 hover:border-primary hover:text-primary md:block"
                        :title="user?.name"
                    >
                        {{ user?.initials }}
                    </Link>

                    <Link
                        v-if="user?.is_admin || user?.is_manager"
                        href="/admin"
                        class="mono-label hidden rounded-[5px] border border-warning-dim px-2.5 py-1.5 text-[10px] tracking-[1px] text-warning no-underline transition-all duration-250 hover:bg-warning hover:text-on-warning lg:block"
                    >
                        Admin
                    </Link>

                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="mono-label hidden cursor-pointer rounded-[5px] border border-line bg-transparent px-2.5 py-1.5 text-[10px] tracking-[1px] text-ink-sec transition-all duration-250 hover:border-negative hover:text-negative md:block"
                    >
                        Sign out
                    </Link>
                </div>

                <button
                    type="button"
                    class="cursor-pointer rounded-[5px] border border-line px-2.5 py-[7px] text-base leading-none text-ink-sec hover:border-ink hover:text-ink md:hidden"
                    aria-label="Toggle navigation"
                    @click="navOpen = !navOpen"
                >
                    &#9776;
                </button>
            </div>
        </nav>

        <FlashMessages />

        <main>
            <slot />
        </main>

        <footer class="border-t border-line px-6 py-7 text-center">
            <p class="mono-label text-[11px] tracking-[1px] text-ink-sec">
                PILOT Training Hub &middot; 1st-Line Support Onboarding
            </p>
        </footer>
    </div>
</template>

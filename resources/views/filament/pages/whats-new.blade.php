{{--
    Renders CHANGELOG.md in a modern, interactive, and beautifully categorized changelog timeline.
    Powered by Alpine.js for zero-latency client-side search, category filtering, and release navigation.
--}}
<x-filament-panels::page>
    @php
        $releases = $this->releases();
        $summary = $this->summary();
    @endphp

    @if (count($releases) > 0)
        <div
            x-data="{
                search: '',
                activeCategory: 'all',
                activeRelease: 'all',
                hasSearchResults() {
                    if (! this.search.trim()) return true;
                    const q = this.search.toLowerCase().trim();
                    const text = this.$el.innerText.toLowerCase();
                    return text.includes(q);
                }
            }"
            class="space-y-8"
        >
            {{-- ─── HERO HEADER & METRICS BANNER ──────────────────────────────── --}}
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-slate-900 to-gray-950 p-6 sm:p-8 text-white shadow-xl ring-1 ring-white/10 dark:from-gray-950 dark:via-gray-900 dark:to-black"
            >
                {{-- Decorative ambient background glow --}}
                <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-primary-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-20 right-1/3 h-64 w-64 rounded-full bg-emerald-500/15 blur-3xl"></div>

                <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl space-y-3">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-primary-200 ring-1 ring-inset ring-white/20 backdrop-blur-sm">
                            <svg class="h-3.5 w-3.5 text-primary-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" />
                            </svg>
                            <span>Platform Changelog & Release Notes</span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                            What's New in PILOT Support Hub
                        </h1>

                        <p class="text-sm leading-relaxed text-gray-300">
                            A complete, chronological record of product updates, competency ladder features,
                            diagnostic tool upgrades, and system improvements across the platform.
                        </p>
                    </div>

                    {{-- Summary KPI Cards --}}
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 shrink-0">
                        <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-md">
                            <p class="text-xs font-medium text-gray-400">Total Releases</p>
                            <p class="mt-1 text-2xl font-bold text-white">{{ $summary['total_releases'] }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Monthly editions</p>
                        </div>

                        <div class="rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-md">
                            <p class="text-xs font-medium text-gray-400">Total Updates</p>
                            <p class="mt-1 text-2xl font-bold text-primary-400">{{ $summary['total_items'] }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Documented improvements</p>
                        </div>

                        <div class="col-span-2 sm:col-span-1 rounded-xl bg-white/5 p-4 ring-1 ring-white/10 backdrop-blur-md">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-gray-400">Latest Release</p>
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                            </div>
                            <p class="mt-1 text-sm font-bold text-emerald-400 truncate">{{ $summary['latest_release'] ?? '—' }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Currently live</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ─── LIVE SEARCH & CATEGORY FILTER BAR ─────────────────────────── --}}
            <div
                class="sticky top-0 z-20 rounded-2xl bg-white/95 p-4 shadow-sm ring-1 ring-gray-950/5 backdrop-blur-md dark:bg-gray-900/95 dark:ring-white/10"
            >
                <div class="flex flex-col gap-3.5 md:flex-row md:items-center md:justify-between">
                    {{-- Search Input --}}
                    <div class="relative flex-1">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400 dark:text-gray-500">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>

                        <input
                            type="text"
                            x-model="search"
                            placeholder="Filter updates by keyword (e.g. video, rubric, exam, dark mode, sensor)..."
                            class="block w-full rounded-xl border-0 py-2 pl-10 pr-10 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-gray-800 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500 dark:focus:ring-primary-500"
                        />

                        <button
                            type="button"
                            x-show="search.length > 0"
                            @click="search = ''"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            title="Clear search"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Category Filter Pills --}}
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button
                            type="button"
                            @click="activeCategory = 'all'"
                            :class="activeCategory === 'all'
                                ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950 shadow-sm'
                                : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition cursor-pointer"
                        >
                            <span>All</span>
                            <span
                                class="rounded-full px-1.5 py-0.2 text-[10px]"
                                :class="activeCategory === 'all' ? 'bg-white/20 dark:bg-black/20' : 'bg-gray-200 dark:bg-gray-700'"
                            >
                                {{ $summary['total_items'] }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeCategory = 'added'"
                            :class="activeCategory === 'added'
                                ? 'bg-emerald-600 text-white shadow-sm dark:bg-emerald-500'
                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/60'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition cursor-pointer"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                            <span>Added</span>
                            <span
                                class="rounded-full px-1.5 py-0.2 text-[10px]"
                                :class="activeCategory === 'added' ? 'bg-white/20' : 'bg-emerald-200/50 dark:bg-emerald-800/50'"
                            >
                                {{ $summary['total_added'] }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeCategory = 'changed'"
                            :class="activeCategory === 'changed'
                                ? 'bg-sky-600 text-white shadow-sm dark:bg-sky-500'
                                : 'bg-sky-50 text-sky-700 hover:bg-sky-100 dark:bg-sky-950/40 dark:text-sky-300 dark:hover:bg-sky-900/60'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition cursor-pointer"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                            <span>Changed</span>
                            <span
                                class="rounded-full px-1.5 py-0.2 text-[10px]"
                                :class="activeCategory === 'changed' ? 'bg-white/20' : 'bg-sky-200/50 dark:bg-sky-800/50'"
                            >
                                {{ $summary['total_changed'] }}
                            </span>
                        </button>

                        <button
                            type="button"
                            @click="activeCategory = 'fixed'"
                            :class="activeCategory === 'fixed'
                                ? 'bg-amber-600 text-white shadow-sm dark:bg-amber-500'
                                : 'bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/40 dark:text-amber-300 dark:hover:bg-amber-900/60'"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition cursor-pointer"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-amber-400"></span>
                            <span>Fixed</span>
                            <span
                                class="rounded-full px-1.5 py-0.2 text-[10px]"
                                :class="activeCategory === 'fixed' ? 'bg-white/20' : 'bg-amber-200/50 dark:bg-amber-800/50'"
                            >
                                {{ $summary['total_fixed'] }}
                            </span>
                        </button>

                        @if ($summary['total_limitations'] > 0)
                            <button
                                type="button"
                                @click="activeCategory = 'limitations'"
                                :class="activeCategory === 'limitations'
                                    ? 'bg-purple-600 text-white shadow-sm dark:bg-purple-500'
                                    : 'bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 dark:hover:bg-purple-900/60'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition cursor-pointer"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-purple-400"></span>
                                <span>Limitations</span>
                                <span
                                    class="rounded-full px-1.5 py-0.2 text-[10px]"
                                     :class="activeCategory === 'limitations' ? 'bg-white/20' : 'bg-purple-200/50 dark:bg-purple-800/50'"
                                >
                                    {{ $summary['total_limitations'] }}
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Fast Jump to Release Links --}}
                @if (count($releases) > 1)
                    <div class="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3 text-xs text-gray-500 dark:border-white/5 dark:text-gray-400">
                        <span class="font-medium text-gray-400 dark:text-gray-500">Jump to release:</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($releases as $rel)
                                <a
                                    href="#{{ $rel['id'] }}"
                                    class="rounded-md px-2 py-0.5 font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white transition"
                                >
                                    {{ $rel['heading'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- ─── TIMELINE OF RELEASES ──────────────────────────────────────── --}}
            <div class="relative space-y-12">
                {{-- Connecting vertical timeline rail on desktop --}}
                <div class="hidden lg:block absolute left-8 top-6 bottom-6 w-0.5 bg-gradient-to-b from-primary-500 via-gray-200 to-gray-100 dark:from-primary-400 dark:via-gray-800 dark:to-gray-900"></div>

                @foreach ($releases as $index => $release)
                    <article
                        id="{{ $release['id'] }}"
                        class="relative lg:pl-20 scroll-mt-24"
                    >
                        {{-- Timeline Milestone Marker on Desktop --}}
                        <div class="hidden lg:flex absolute left-8 top-6 -translate-x-1/2 h-8 w-8 items-center justify-center rounded-full border-4 border-gray-50 bg-white shadow-sm ring-1 ring-gray-950/10 dark:border-gray-950 dark:bg-gray-900 dark:ring-white/20">
                            @if ($index === 0)
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 shadow-sm"></span>
                            @else
                                <span class="h-2 w-2 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                            @endif
                        </div>

                        {{-- Release Card Container --}}
                        <div
                            class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                        >
                            {{-- Release Header --}}
                            <header
                                class="flex flex-col gap-3 border-b border-gray-200/80 bg-gray-50/50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10 dark:bg-white/[0.02]"
                            >
                                <div class="flex flex-wrap items-center gap-3">
                                    <h2 class="text-xl font-bold tracking-tight text-gray-950 dark:text-white">
                                        {{ $release['heading'] }}
                                    </h2>

                                    @if ($index === 0)
                                        {{-- Only newest month carries the "Latest" badge --}}
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/30"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                            Latest
                                        </span>
                                    @endif
                                </div>

                                {{-- Release breakdown badges --}}
                                <div class="flex flex-wrap items-center gap-1.5 text-xs font-medium">
                                    @if (($release['stats']['added'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700 ring-1 ring-inset ring-emerald-600/10 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/30">
                                            +{{ $release['stats']['added'] }} added
                                        </span>
                                    @endif
                                    @if (($release['stats']['changed'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-sky-50 px-2 py-0.5 text-sky-700 ring-1 ring-inset ring-sky-600/10 dark:bg-sky-400/10 dark:text-sky-400 dark:ring-sky-400/30">
                                            {{ $release['stats']['changed'] }} changed
                                        </span>
                                    @endif
                                    @if (($release['stats']['fixed'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-amber-700 ring-1 ring-inset ring-amber-600/10 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/30">
                                            {{ $release['stats']['fixed'] }} fixed
                                        </span>
                                    @endif
                                    @if (($release['stats']['limitations'] ?? 0) > 0)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-0.5 text-purple-700 ring-1 ring-inset ring-purple-600/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30">
                                            {{ $release['stats']['limitations'] }} notices
                                        </span>
                                    @endif
                                </div>
                            </header>

                            {{-- Release Body: Categorized Sections --}}
                            <div class="p-6 space-y-8">
                                @foreach ($release['sections'] as $section)
                                    <div
                                        x-show="activeCategory === 'all' || activeCategory === '{{ $section['type'] }}'"
                                        class="space-y-4"
                                    >
                                        {{-- Category Section Header --}}
                                        <div class="flex items-center gap-2.5">
                                            @if ($section['type'] === 'added')
                                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                                    </svg>
                                                </span>
                                                <h3 class="text-sm font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">
                                                    Added & New Features
                                                </h3>
                                            @elseif ($section['type'] === 'changed')
                                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-400">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H3.75a.75.75 0 00-.75.75v4.483a.75.75 0 001.5 0v-2.099l.343.344a7 7 0 0011.89-3.239.75.75 0 00-1.42-.444zm-10.624-2.85a5.5 5.5 0 019.2-2.466l.312.311h-2.433a.75.75 0 000 1.5h4.483a.75.75 0 00.75-.75V2.686a.75.75 0 00-1.5 0v2.099l-.343-.344a7 7 0 00-11.89 3.239.75.75 0 001.42.444z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                <h3 class="text-sm font-bold uppercase tracking-wider text-sky-800 dark:text-sky-400">
                                                    Improvements & Changes
                                                </h3>
                                            @elseif ($section['type'] === 'fixed')
                                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                <h3 class="text-sm font-bold uppercase tracking-wider text-amber-800 dark:text-amber-400">
                                                    Bug Fixes & Resolved Issues
                                                </h3>
                                            @elseif ($section['type'] === 'limitations')
                                                <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-400">
                                                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                                    </svg>
                                                </span>
                                                <h3 class="text-sm font-bold uppercase tracking-wider text-purple-800 dark:text-purple-400">
                                                    Known Limitations & System Notes
                                                </h3>
                                            @else
                                                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                                                    {{ $section['title'] }}
                                                </h3>
                                            @endif

                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                {{ count($section['items']) }}
                                            </span>
                                        </div>

                                        {{-- Items Grid / Stack --}}
                                        @if (count($section['items']) > 0)
                                            <div class="grid gap-3">
                                                @foreach ($section['items'] as $item)
                                                    <div
                                                        x-show="search.trim() === '' || {{ json_encode($item['search_text']) }}.includes(search.toLowerCase().trim())"
                                                        @class([
                                                            'group relative rounded-xl border bg-white p-4.5 shadow-sm transition-all duration-150',
                                                            'hover:shadow-md dark:bg-gray-900/60',
                                                            'border-emerald-200/60 hover:border-emerald-500/40 dark:border-emerald-900/40 dark:hover:border-emerald-500/50' => $section['type'] === 'added',
                                                            'border-sky-200/60 hover:border-sky-500/40 dark:border-sky-900/40 dark:hover:border-sky-500/50' => $section['type'] === 'changed',
                                                            'border-amber-200/60 hover:border-amber-500/40 dark:border-amber-900/40 dark:hover:border-amber-500/50' => $section['type'] === 'fixed',
                                                            'border-purple-200/60 hover:border-purple-500/40 bg-purple-50/20 dark:border-purple-900/40 dark:hover:border-purple-500/50 dark:bg-purple-950/10' => $section['type'] === 'limitations',
                                                            'border-gray-200 hover:border-gray-400 dark:border-white/10 dark:hover:border-white/20' => $section['type'] === 'other',
                                                        ])
                                                    >
                                                        {{-- Left Category Accent Line --}}
                                                        <div
                                                            @class([
                                                                'absolute left-0 top-3 bottom-3 w-1 rounded-r-full',
                                                                'bg-emerald-500 dark:bg-emerald-400' => $section['type'] === 'added',
                                                                'bg-sky-500 dark:bg-sky-400' => $section['type'] === 'changed',
                                                                'bg-amber-500 dark:bg-amber-400' => $section['type'] === 'fixed',
                                                                'bg-purple-500 dark:bg-purple-400' => $section['type'] === 'limitations',
                                                                'bg-gray-400 dark:bg-gray-500' => $section['type'] === 'other',
                                                            ])
                                                        ></div>

                                                        <div class="pl-2.5">
                                                            <div
                                                                @class([
                                                                    'prose prose-sm max-w-none dark:prose-invert',
                                                                    'prose-p:leading-relaxed prose-p:my-0 prose-p:text-gray-700 dark:prose-p:text-gray-300',
                                                                    'prose-strong:text-gray-950 dark:prose-strong:text-white prose-strong:font-semibold',
                                                                    'prose-code:rounded-md prose-code:bg-gray-100 dark:prose-code:bg-white/10 prose-code:px-1.5 prose-code:py-0.5 prose-code:text-xs prose-code:font-mono prose-code:text-gray-800 dark:prose-code:text-gray-200',
                                                                    'prose-a:text-primary-600 dark:prose-a:text-primary-400 prose-a:underline hover:prose-a:text-primary-500',
                                                                ])
                                                            >
                                                                {!! $item['html'] !!}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            {{-- Fallback for unstructured content --}}
                                            <div class="prose prose-sm max-w-none dark:prose-invert">
                                                {!! $section['raw_html'] !!}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    @else
        {{-- Empty state when CHANGELOG.md has no entries or is missing --}}
        <div
            @class([
                'rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-gray-950/5',
                'dark:bg-gray-900 dark:ring-white/10',
            ])
        >
            <x-filament::icon
                icon="heroicon-o-sparkles"
                class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500"
            />

            <h2 class="mt-3 text-base font-semibold text-gray-950 dark:text-white">
                Nothing recorded yet
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Changes are listed in <code>CHANGELOG.md</code> at the root of the
                project. Add a month heading to it and it appears here.
            </p>
        </div>
    @endif
</x-filament-panels::page>


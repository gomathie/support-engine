{{--
    CHANGELOG.md, one card per month.

    Filtering runs in Alpine over a matrix built server-side, so a section with
    nothing left in it and a month with nothing left in it both disappear. The
    previous version filtered only the items, which left empty headings and
    empty month cards standing behind them.

    Deliberately flat: no gradients, no glow, no backdrop blur. That is the
    house style — see the note at the top of resources/css/app.css — and it is
    what makes a wall of release notes readable.
--}}
<x-filament-panels::page>
    @php
        $releases = $this->releases();
        $summary = $this->summary();

        // Section type → the classes carrying its colour. One place, so a fifth
        // category is one entry rather than five branches.
        $tones = [
            'added' => 'bg-success-500',
            'changed' => 'bg-info-500',
            'fixed' => 'bg-warning-500',
            'limitations' => 'bg-danger-500',
            'other' => 'bg-gray-400',
        ];

        $labels = [
            'added' => 'Added',
            'changed' => 'Changed',
            'fixed' => 'Fixed',
            'limitations' => 'Known limitations',
        ];

        // What Alpine filters against: the type, and the lowercased text of
        // each item. A section the parser could not split into bullets
        // contributes its whole body as one blob rather than being unsearchable.
        $matrix = collect($releases)->map(fn ($release) => [
            'sections' => collect($release['sections'])->map(fn ($section) => [
                'type' => $section['type'],
                'items' => count($section['items'])
                    ? collect($section['items'])->pluck('search_text')->values()->all()
                    : [strtolower(strip_tags($section['raw_html']))],
            ])->values()->all(),
        ])->values()->all();
    @endphp

    @if (count($releases) === 0)
        <div class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <x-filament::icon icon="heroicon-o-sparkles" class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500" />

            <h2 class="mt-3 text-base font-semibold text-gray-950 dark:text-white">
                Nothing recorded yet
            </h2>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Changes are listed in <code>CHANGELOG.md</code> at the root of the
                project. Add a month heading to it and it appears here.
            </p>
        </div>
    @else
        <div
            x-data="{
                search: '',
                category: 'all',
                releases: @js($matrix),

                itemVisible(r, s, i) {
                    const section = this.releases[r].sections[s];

                    if (this.category !== 'all' && section.type !== this.category) {
                        return false;
                    }

                    const q = this.search.trim().toLowerCase();

                    return q === '' || section.items[i].includes(q);
                },

                sectionVisible(r, s) {
                    return this.releases[r].sections[s].items
                        .some((item, i) => this.itemVisible(r, s, i));
                },

                releaseVisible(r) {
                    return this.releases[r].sections
                        .some((section, s) => this.sectionVisible(r, s));
                },

                get anyVisible() {
                    return this.releases.some((release, r) => this.releaseVisible(r));
                },
            }"
            class="flex flex-col gap-6"
        >
            <p class="max-w-3xl text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                Every change that alters what somebody can see or do, newest first.
                <span class="font-semibold text-gray-950 dark:text-white">{{ $summary['total_items'] }}</span>
                {{ Str::plural('entry', $summary['total_items']) }} across
                <span class="font-semibold text-gray-950 dark:text-white">{{ $summary['total_releases'] }}</span>
                {{ Str::plural('month', $summary['total_releases']) }}.
            </p>

            {{-- ─── FILTERS ────────────────────────────────────────────── --}}
            <div class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 md:flex-row md:items-center dark:bg-gray-900 dark:ring-white/10">
                <div class="flex-1">
                    <label for="changelog-search" class="sr-only">Search the changelog</label>

                    <input
                        id="changelog-search"
                        type="search"
                        x-model="search"
                        placeholder="Search — video, rubric, exam, sensor…"
                        class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-950 ring-1 ring-inset ring-gray-950/10 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        type="button"
                        @click="category = 'all'"
                        :class="category === 'all'
                            ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                            : 'text-gray-600 ring-1 ring-inset ring-gray-950/10 hover:bg-gray-50 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/5'"
                        class="cursor-pointer rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                    >
                        All
                    </button>

                    @foreach (['added', 'changed', 'fixed', 'limitations'] as $type)
                        @continue($summary['total_'.$type] === 0)

                        <button
                            type="button"
                            @click="category = '{{ $type }}'"
                            :class="category === '{{ $type }}'
                                ? 'bg-gray-950 text-white dark:bg-white dark:text-gray-950'
                                : 'text-gray-600 ring-1 ring-inset ring-gray-950/10 hover:bg-gray-50 dark:text-gray-300 dark:ring-white/10 dark:hover:bg-white/5'"
                            class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        >
                            <span class="h-1.5 w-1.5 rounded-full {{ $tones[$type] }}"></span>
                            {{ $labels[$type] }}
                            <span class="tabular-nums opacity-60">{{ $summary['total_'.$type] }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Nothing matched. Said once, rather than as a page of empty cards. --}}
            <p
                x-cloak
                x-show="! anyVisible"
                class="rounded-xl bg-white px-6 py-10 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:text-gray-400 dark:ring-white/10"
            >
                Nothing matches
                <span class="font-semibold text-gray-950 dark:text-white" x-text="search.trim() || 'that filter'"></span>.
            </p>

            {{-- ─── RELEASES ───────────────────────────────────────────── --}}
            @foreach ($releases as $r => $release)
                <section
                    id="{{ $release['id'] }}"
                    x-show="releaseVisible({{ $r }})"
                    class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                >
                    <header class="flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-gray-950/5 px-6 py-4 dark:border-white/10">
                        <h2 class="text-base font-bold text-gray-950 dark:text-white">
                            {{ $release['heading'] }}
                        </h2>

                        @if ($r === 0)
                            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-semibold text-success-700 ring-1 ring-inset ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/30">
                                Latest
                            </span>
                        @endif

                        <div class="flex flex-wrap items-center gap-3 sm:ml-auto">
                            @foreach ($release['stats'] as $type => $count)
                                @continue($type === 'total' || $count === 0 || ! isset($labels[$type]))

                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $tones[$type] }}"></span>
                                    {{ $count }} {{ strtolower($labels[$type]) }}
                                </span>
                            @endforeach
                        </div>
                    </header>

                    <div class="flex flex-col gap-6 px-6 py-5">
                        @foreach ($release['sections'] as $s => $section)
                            @php $dot = $tones[$section['type']] ?? $tones['other']; @endphp

                            <div x-show="sectionVisible({{ $r }}, {{ $s }})" class="flex flex-col gap-3">
                                <h3 class="flex items-center gap-2 text-xs font-bold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $dot }}"></span>
                                    {{ $labels[$section['type']] ?? $section['title'] }}
                                </h3>

                                @if (count($section['items']))
                                    <ul class="flex flex-col gap-2.5">
                                        @foreach ($section['items'] as $i => $item)
                                            <li
                                                x-show="itemVisible({{ $r }}, {{ $s }}, {{ $i }})"
                                                class="flex gap-3"
                                            >
                                                <span class="mt-2 h-1 w-1 shrink-0 rounded-full {{ $dot }}"></span>

                                                <div class="prose prose-sm dark:prose-invert prose-p:my-0 prose-p:leading-relaxed prose-p:text-gray-600 dark:prose-p:text-gray-300 prose-strong:font-semibold prose-strong:text-gray-950 dark:prose-strong:text-white prose-code:rounded prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:text-xs prose-code:font-normal prose-code:before:content-none prose-code:after:content-none dark:prose-code:bg-white/10 prose-a:text-primary-600 dark:prose-a:text-primary-400 max-w-none">
                                                    {!! $item['html'] !!}
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    {{-- The parser found no bullets — show the section as written. --}}
                                    <div class="prose prose-sm dark:prose-invert max-w-none">
                                        {!! $section['raw_html'] !!}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>

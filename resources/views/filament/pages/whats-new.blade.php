{{--
    Renders the parsed CHANGELOG.md, one card per month.

    The `prose` classes come from Filament's own typography, so the changelog
    picks up the panel's light and dark themes rather than carrying its own.
--}}
<x-filament-panels::page>
    @php($releases = $this->releases())

    @forelse ($releases as $index => $release)
        <section
            @class([
                'fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5',
                'dark:bg-gray-900 dark:ring-white/10',
            ])
        >
            <header
                @class([
                    'flex items-center gap-3 border-b border-gray-200 px-6 py-4',
                    'dark:border-white/10',
                ])
            >
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                    {{ $release['heading'] }}
                </h2>

                @if ($index === 0)
                    {{-- Only the newest month is "latest"; the rest are history. --}}
                    <span
                        @class([
                            'rounded-md bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-600',
                            'ring-1 ring-inset ring-primary-600/10',
                            'dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/30',
                        ])
                    >
                        Latest
                    </span>
                @endif
            </header>

            <div class="px-6 py-5">
                <div
                    @class([
                        'prose prose-sm max-w-none dark:prose-invert',
                        'prose-headings:text-sm prose-headings:font-semibold prose-headings:tracking-wide',
                        'prose-headings:text-gray-500 dark:prose-headings:text-gray-400',
                        'prose-headings:uppercase prose-headings:mt-6 prose-headings:mb-2',
                        'prose-li:my-1 prose-p:text-gray-600 dark:prose-p:text-gray-300',
                        'prose-strong:text-gray-950 dark:prose-strong:text-white',
                    ])
                >
                    {{-- Markdown from a repository file, parsed with raw HTML
                         stripped in WhatsNew::toHtml(). --}}
                    {!! $release['body'] !!}
                </div>
            </div>
        </section>
    @empty
        {{-- Plain markup rather than a Filament component: Blade resolves
             component tags at compile time, so an unknown one breaks the whole
             template, not only the branch it sits in. --}}
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
    @endforelse
</x-filament-panels::page>

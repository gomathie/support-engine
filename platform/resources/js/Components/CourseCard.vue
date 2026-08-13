<script setup>
/**
 * The feature card from index.html, generalised.
 *
 * The prototype styled three fixed cards with :nth-child selectors — card 1
 * blue, card 2 amber, card 3 slate. That does not survive a dynamic list, so
 * the same three-way palette is now keyed off the course's position in the
 * grid, preserving the look while working for any number of courses.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    course: { type: Object, required: true },
    index: { type: Number, default: 0 },
    showProgress: { type: Boolean, default: true },
});

const accents = [
    {
        glow: 'from-[rgba(2,132,199,.06)] to-[rgba(234,179,8,.04)]',
        icon: 'bg-primary-bg text-primary',
        cta: 'text-primary',
    },
    {
        glow: 'from-[rgba(234,179,8,.06)] to-[rgba(200,128,128,.04)]',
        icon: 'bg-warning-bg text-warning-dim dark:text-warning',
        cta: 'text-[#a16207] dark:text-warning',
    },
    {
        glow: 'from-[rgba(31,78,121,.08)] to-[rgba(2,132,199,.04)]',
        icon: 'bg-[rgba(31,78,121,.15)] text-[#5B9BD5] dark:bg-[rgba(91,155,213,.15)]',
        cta: 'text-[#2f6ca8] dark:text-[#5B9BD5]',
    },
];

const accent = computed(() => accents[props.index % accents.length]);

const icon = computed(() => {
    const category = (props.course.category ?? '').toLowerCase();
    if (category.includes('prep') || category.includes('check')) return '📋';
    if (category.includes('admin')) return '⚙️';
    if (category.includes('skill')) return '📖';
    return '🔧';
});
</script>

<template>
    <Link
        :href="route('courses.show', course.slug)"
        class="group relative flex flex-col overflow-hidden rounded-2xl border border-line bg-surface p-8 pb-7 text-ink no-underline transition-all duration-250 hover:-translate-y-1 hover:border-ink-dis hover:shadow-[0_16px_48px_rgba(0,0,0,.1)] focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary dark:hover:shadow-[0_16px_48px_rgba(0,0,0,.3)]"
    >
        <div
            class="pointer-events-none absolute inset-0 rounded-2xl bg-gradient-to-br opacity-0 transition-opacity duration-350 group-hover:opacity-100"
            :class="accent.glow"
        ></div>

        <div class="relative flex items-start justify-between">
            <div
                class="mb-5 flex h-13 w-13 items-center justify-center rounded-xl text-2xl"
                :class="accent.icon"
            >
                {{ icon }}
            </div>
            <StatusPill
                v-if="showProgress"
                :label="course.status_label"
                :tone="course.status_tone"
            />
        </div>

        <div class="relative flex flex-1 flex-col">
            <div class="mono-label mb-2 text-[9px] tracking-[2px] text-ink-sec">
                {{ course.category || 'Training' }}
                <span v-if="course.is_required" class="text-negative">&middot; Required</span>
            </div>

            <h2 class="mb-2.5 text-xl leading-tight font-bold">{{ course.title }}</h2>

            <p class="flex-1 text-sm leading-relaxed text-ink-sec">
                {{ course.summary }}
            </p>

            <div v-if="showProgress" class="mt-4">
                <ProgressBar :percentage="course.percentage" :tone="course.status_tone" />
                <div class="mono-label mt-1.5 flex justify-between text-[10px] tracking-[1px] text-ink-sec">
                    <span>{{ course.completed_lessons ?? 0 }}/{{ course.total_lessons ?? course.lesson_count ?? 0 }} lessons</span>
                    <span>{{ Math.round(course.percentage) }}%</span>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-2 border-t border-line pt-4">
                <span class="mono-label text-[11px] tracking-[1.5px]" :class="accent.cta">
                    {{ course.percentage > 0 ? 'Continue' : 'Start course' }}
                </span>
                <span
                    class="text-base transition-transform duration-250 group-hover:translate-x-1"
                    :class="accent.cta"
                >
                    →
                </span>
            </div>
        </div>
    </Link>
</template>

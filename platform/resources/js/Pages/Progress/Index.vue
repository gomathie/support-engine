<script setup>
/**
 * The Training Tracker.
 *
 * A direct descendant of pages/training-tracker.html: the same 108px gauge and
 * title block, the same collapsible sections with an amber flag chip and a
 * count, the same day cards that turn green when every item in them is ticked,
 * the same toggle-switch rows, the same right-aligned reset button.
 *
 * What changed is underneath. The prototype rendered from a `DATA` const,
 * counted ticks in a `STATE` object, and wrote that object to an API that did
 * not exist. Everything here arrives as props from course_progress and
 * lesson_progress, and every tick is a round trip.
 */
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import ProgressGauge from '@/Components/ProgressGauge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import ChecklistItem from '@/Components/ChecklistItem.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    overall: { type: Object, required: true },
    sections: { type: Array, default: () => [] },
});

// Collapse is pure presentation, so it stays in the browser — one of the few
// pieces of prototype state that was right to keep client-side.
const collapsed = ref({});
const toggleSection = (id) => (collapsed.value[id] = !collapsed.value[id]);

const confirming = ref(null);

function reset(slug) {
    router.delete(route('progress.reset', slug), {
        preserveScroll: true,
        onFinish: () => (confirming.value = null),
    });
}
</script>

<template>
    <Head title="My Progress" />

    <EmployeeLayout>
        <div class="min-h-screen px-5 pt-7 pb-15 text-sm">
            <!-- ─── HEADER ──────────────────────────────────── -->
            <div class="mx-auto mb-6 flex max-w-[880px] flex-wrap items-center gap-6">
                <ProgressGauge :percentage="overall.percentage" />

                <div class="min-w-[220px] flex-1">
                    <p class="mono-label mb-1.5 text-[11px] tracking-[3px] text-primary">
                        Flight plan // onboarding
                    </p>
                    <h1 class="mb-1.5 text-xl font-bold tracking-[0.5px]">
                        PILOT system training tracker
                    </h1>
                    <p class="text-[13px] text-ink-sec">
                        {{ overall.courses_completed }} of {{ overall.courses_total }} courses
                        complete
                    </p>
                    <div class="mt-2.5 flex flex-wrap gap-4.5">
                        <div class="font-mono text-xs text-ink-sec">
                            <b class="text-sm text-ink">{{ overall.completed_lessons }}</b>
                            / {{ overall.total_lessons }} tasks checked off
                        </div>
                    </div>
                </div>
            </div>

            <!-- ─── SECTIONS ────────────────────────────────── -->
            <div v-if="sections.length">
                <div v-for="section in sections" :key="section.course_id" class="mx-auto mb-5 max-w-[880px]">
                    <button
                        type="button"
                        class="mb-2.5 flex w-full cursor-pointer items-center gap-2.5 rounded-md border border-line bg-surface-alt px-3.5 py-2.5 text-left select-none"
                        :aria-expanded="!collapsed[section.course_id]"
                        @click="toggleSection(section.course_id)"
                    >
                        <span
                            v-if="section.flag"
                            class="rounded-[3px] bg-warning px-2 py-0.5 font-mono text-[11px] font-bold tracking-[1px] text-on-warning"
                        >
                            {{ section.flag }}
                        </span>
                        <h2 class="flex-1 text-[15px] font-bold">{{ section.title }}</h2>
                        <span class="font-mono text-xs text-ink-sec">
                            {{ section.completed_lessons }}/{{ section.total_lessons }}
                        </span>
                        <span
                            class="text-xs text-ink-dis transition-transform duration-150"
                            :class="collapsed[section.course_id] ? '-rotate-90' : ''"
                        >
                            ▾
                        </span>
                    </button>

                    <ProgressBar
                        v-show="!collapsed[section.course_id]"
                        :percentage="section.percentage"
                        class="mb-3"
                    />

                    <div v-show="!collapsed[section.course_id]" class="flex flex-col gap-2.5">
                        <div
                            v-for="module in section.modules"
                            :key="module.id"
                            class="rounded-lg border px-4 py-3.5 transition-colors"
                            :class="
                                module.completed_count === module.total_count && module.total_count > 0
                                    ? 'border-positive bg-positive-bg'
                                    : 'border-line bg-surface'
                            "
                        >
                            <div class="mb-1 flex items-baseline gap-2.5">
                                <span class="font-mono text-xs font-bold tracking-[1px] text-warning">
                                    {{ module.label }}
                                </span>
                                <h3 class="flex-1 text-sm font-bold">{{ module.title }}</h3>
                                <span class="font-mono text-[11px] text-ink-dis">
                                    {{ module.completed_count }}/{{ module.total_count }}
                                </span>
                            </div>

                            <p v-if="module.topics" class="mb-2.5 text-xs leading-relaxed text-ink-sec">
                                {{ module.topics }}
                            </p>

                            <div class="flex flex-col gap-1.5">
                                <ChecklistItem
                                    v-for="item in module.items"
                                    :key="item.id"
                                    :item="item"
                                    :course-slug="section.slug"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- ─── RESET ───────────────────────────── -->
                    <div v-if="section.can_reset" class="mt-4 text-right">
                        <template v-if="confirming === section.slug">
                            <span class="mr-2 font-mono text-[11px] text-ink-sec">
                                Clear all progress for this course?
                            </span>
                            <button
                                type="button"
                                class="mr-1 cursor-pointer rounded border border-negative px-3 py-1.5 font-mono text-[11px] text-negative"
                                @click="reset(section.slug)"
                            >
                                yes, reset
                            </button>
                            <button
                                type="button"
                                class="cursor-pointer rounded border border-line px-3 py-1.5 font-mono text-[11px] text-ink-dis"
                                @click="confirming = null"
                            >
                                cancel
                            </button>
                        </template>
                        <button
                            v-else
                            type="button"
                            class="cursor-pointer rounded border border-line px-3 py-1.5 font-mono text-[11px] text-ink-dis transition-colors hover:border-warning-dim hover:text-warning"
                            @click="confirming = section.slug"
                        >
                            reset progress
                        </button>
                    </div>
                </div>
            </div>

            <div v-else class="mx-auto max-w-[880px]">
                <EmptyState
                    icon="📋"
                    title="Nothing to track yet"
                    description="Once you are enrolled in a course, every lesson in it turns up here as a checklist item."
                >
                    <Link
                        :href="route('courses.index')"
                        class="mono-label rounded-[5px] border border-primary bg-primary px-4 py-2 text-[11px] tracking-[1.5px] text-on-accent no-underline"
                    >
                        Browse courses
                    </Link>
                </EmptyState>
            </div>
        </div>
    </EmployeeLayout>
</template>

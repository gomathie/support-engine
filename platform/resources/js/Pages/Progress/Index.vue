<script setup>
/**
 * The onboarding tracker: every assigned course broken into its modules, each
 * module a checklist the employee ticks off as they go.
 *
 * Modules read as milestones — a completed one turns green and collapses out of
 * the way, so what is left to do stays the visible part.
 */
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import ProgressGauge from '@/Components/ProgressGauge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import ChecklistItem from '@/Components/ChecklistItem.vue';
import StatusPill from '@/Components/StatusPill.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    overall: { type: Object, required: true },
    sections: { type: Array, default: () => [] },
});

// Collapse is presentation state, so it stays in the browser. Finished
// milestones start collapsed — they are done, and the point of this page is
// what is still outstanding.
const collapsed = ref(
    Object.fromEntries(
        props.sections.flatMap((section) =>
            section.modules.map((module) => [
                module.id,
                module.total_count > 0 && module.completed_count === module.total_count,
            ]),
        ),
    ),
);

const toggle = (id) => (collapsed.value[id] = !collapsed.value[id]);

const confirming = ref(null);

function reset(slug) {
    router.delete(route('progress.reset', slug), {
        preserveScroll: true,
        onFinish: () => (confirming.value = null),
    });
}

const remaining = computed(() => props.overall.total_lessons - props.overall.completed_lessons);
</script>

<template>
    <Head title="My Progress" />

    <EmployeeLayout>
        <!-- ─── HEADER ──────────────────────────────────────── -->
        <div class="card mb-8 flex flex-wrap items-center gap-6 p-6">
            <ProgressGauge :percentage="overall.percentage" />

            <div class="min-w-[220px] flex-1">
                <h1 class="mb-1 text-xl font-extrabold text-navy">Your onboarding progress</h1>

                <p class="mb-3 text-sm text-ink-sec">
                    <template v-if="remaining > 0">
                        {{ overall.completed_lessons }} of {{ overall.total_lessons }} steps done —
                        {{ remaining }} to go.
                    </template>
                    <template v-else-if="overall.total_lessons > 0">
                        Everything assigned to you is complete. Nice work.
                    </template>
                </p>

                <div class="flex flex-wrap gap-2">
                    <StatusPill
                        :label="`${overall.courses_completed} of ${overall.courses_total} courses`"
                        :tone="
                            overall.courses_completed === overall.courses_total
                                ? 'positive'
                                : 'primary'
                        "
                    />
                </div>
            </div>
        </div>

        <!-- ─── COURSES ─────────────────────────────────────── -->
        <div v-if="sections.length" class="flex flex-col gap-8">
            <section v-for="section in sections" :key="section.course_id">
                <div class="mb-3 flex flex-wrap items-center gap-3">
                    <span v-if="section.flag" class="chip bg-brand-soft text-brand">
                        {{ section.flag }}
                    </span>

                    <h2 class="flex-1 text-lg font-bold text-navy">
                        <Link
                            :href="route('courses.show', section.slug)"
                            class="no-underline hover:text-brand"
                        >
                            {{ section.title }}
                        </Link>
                    </h2>

                    <span class="text-sm font-semibold text-ink-sec">
                        {{ section.completed_lessons }}/{{ section.total_lessons }}
                    </span>
                </div>

                <ProgressBar
                    :percentage="section.percentage"
                    :tone="section.status === 'completed' ? 'positive' : 'primary'"
                    class="mb-4"
                />

                <div class="flex flex-col gap-3">
                    <div
                        v-for="module in section.modules"
                        :key="module.id"
                        class="card overflow-hidden"
                        :class="
                            module.total_count > 0 && module.completed_count === module.total_count
                                ? 'border-ok/40'
                                : ''
                        "
                    >
                        <button
                            type="button"
                            class="flex w-full cursor-pointer items-center gap-3 px-5 py-4 text-left transition-colors hover:bg-surface-alt"
                            :aria-expanded="!collapsed[module.id]"
                            @click="toggle(module.id)"
                        >
                            <span
                                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="
                                    module.total_count > 0 &&
                                    module.completed_count === module.total_count
                                        ? 'bg-ok text-white'
                                        : 'bg-surface-alt text-ink-sec'
                                "
                            >
                                <template
                                    v-if="
                                        module.total_count > 0 &&
                                        module.completed_count === module.total_count
                                    "
                                >
                                    ✓
                                </template>
                                <template v-else>
                                    {{ module.completed_count }}
                                </template>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-navy">
                                    {{ module.label }}
                                </span>
                                <span class="block truncate text-sm text-ink-sec">
                                    {{ module.title }}
                                </span>
                            </span>

                            <span class="shrink-0 text-xs font-medium text-ink-dis">
                                {{ module.completed_count }}/{{ module.total_count }}
                            </span>

                            <svg
                                class="h-4 w-4 shrink-0 fill-none stroke-current stroke-2 text-ink-dis transition-transform"
                                :class="collapsed[module.id] ? '-rotate-90' : ''"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                            </svg>
                        </button>

                        <div v-show="!collapsed[module.id]" class="border-t border-line px-3 pt-2 pb-3">
                            <p
                                v-if="module.topics"
                                class="mb-2 px-2 text-sm leading-relaxed text-ink-sec"
                            >
                                {{ module.topics }}
                            </p>

                            <ChecklistItem
                                v-for="item in module.items"
                                :key="item.id"
                                :item="item"
                                :course-slug="section.slug"
                            />
                        </div>
                    </div>
                </div>

                <div v-if="section.can_reset" class="mt-3 text-right">
                    <template v-if="confirming === section.slug">
                        <span class="mr-2 text-sm text-ink-sec">
                            Clear all progress for this course?
                        </span>
                        <button
                            type="button"
                            class="mr-1 cursor-pointer rounded-lg border border-negative px-3 py-1.5 text-sm font-medium text-negative"
                            @click="reset(section.slug)"
                        >
                            Yes, reset
                        </button>
                        <button
                            type="button"
                            class="cursor-pointer rounded-lg border border-line px-3 py-1.5 text-sm font-medium text-ink-sec"
                            @click="confirming = null"
                        >
                            Cancel
                        </button>
                    </template>
                    <button
                        v-else
                        type="button"
                        class="cursor-pointer text-sm font-medium text-ink-dis transition-colors hover:text-negative"
                        @click="confirming = section.slug"
                    >
                        Reset progress
                    </button>
                </div>
            </section>
        </div>

        <EmptyState
            v-else
            title="Nothing to track yet"
            description="Once you are enrolled in a course, every lesson in it turns up here as a step you can tick off."
        >
            <Link
                :href="route('courses.index')"
                class="inline-block rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-brand-hover"
            >
                Browse courses
            </Link>
        </EmptyState>
    </EmployeeLayout>
</template>

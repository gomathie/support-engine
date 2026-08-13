<script setup>
/**
 * Framed around a new support hire's first weeks.
 *
 * The order is deliberate: what to do next, then what is due, then everything
 * assigned. Somebody who has not started anything gets a directive "start
 * here" panel instead of a wall of cards.
 */
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import DashboardStat from '@/Components/DashboardStat.vue';
import EmptyState from '@/Components/EmptyState.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    stats: { type: Object, required: true },
    courses: { type: Array, default: () => [] },
    due_soon: { type: Array, default: () => [] },
    recent_results: { type: Array, default: () => [] },
    recommended: { type: Array, default: () => [] },
    certificates_count: { type: Number, default: 0 },
    next_lesson: { type: Object, default: null },
    is_new_starter: { type: Boolean, default: false },
});

const user = usePage().props.auth.user;
const firstName = computed(() => user?.name?.split(' ')[0] ?? '');

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) : '';

const inProgress = computed(() => props.courses.filter((c) => c.status === 'in_progress'));
const notStarted = computed(() => props.courses.filter((c) => c.status === 'not_started'));
const finished = computed(() => props.courses.filter((c) => c.status === 'completed'));
</script>

<template>
    <Head title="Dashboard" />

    <EmployeeLayout>
        <!-- ─── HERO ────────────────────────────────────────── -->
        <section class="brand-gradient mb-8 rounded-2xl p-7 text-white sm:p-9">
            <p class="mb-1.5 text-sm font-medium text-white/70">
                {{ is_new_starter ? 'Welcome to the team' : 'Welcome back' }}
            </p>

            <h1 class="mb-2 text-2xl font-extrabold text-white sm:text-3xl">
                {{ firstName }}
            </h1>

            <p class="max-w-xl text-sm leading-relaxed text-white/80 sm:text-base">
                {{
                    is_new_starter
                        ? 'Your onboarding is mapped out below. Work through it at your own pace — everything you tick is saved, so you can stop and pick up where you left off.'
                        : 'Short, focused lessons that get you productive on the PILOT platform.'
                }}
            </p>

            <!-- Next lesson: the primary action on this page. -->
            <div
                v-if="next_lesson"
                class="mt-6 rounded-xl bg-white/10 p-4 backdrop-blur-none sm:p-5"
            >
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="mb-1 text-xs font-semibold tracking-wide text-white/60 uppercase">
                            {{ is_new_starter ? 'Start here' : 'Pick up where you left off' }}
                        </p>
                        <p class="truncate text-base font-bold text-white">
                            {{ next_lesson.title }}
                        </p>
                        <p class="truncate text-sm text-white/70">
                            {{ next_lesson.course_title }}
                            <span v-if="next_lesson.module_title">
                                · {{ next_lesson.module_title }}
                            </span>
                        </p>
                    </div>

                    <Link
                        :href="next_lesson.url"
                        class="shrink-0 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-navy no-underline transition-colors hover:bg-slate-100"
                    >
                        {{ next_lesson.percentage > 0 ? 'Continue' : 'Begin' }} →
                    </Link>
                </div>

                <div v-if="next_lesson.total_lessons" class="mt-4">
                    <div class="h-2 overflow-hidden rounded-full bg-white/20">
                        <div
                            class="h-full rounded-full bg-white transition-[width] duration-500"
                            :style="{ width: `${next_lesson.percentage}%` }"
                        ></div>
                    </div>
                    <p class="mt-1.5 text-xs text-white/70">
                        {{ next_lesson.completed_lessons }} of
                        {{ next_lesson.total_lessons }} lessons in this course
                    </p>
                </div>
            </div>
        </section>

        <!-- ─── STATS ──────────────────────────────────────── -->
        <div class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <DashboardStat :value="stats.assigned" label="Assigned" />
            <DashboardStat :value="stats.in_progress" label="In progress" tone="brand" />
            <DashboardStat :value="stats.completed" label="Completed" tone="positive" />
            <DashboardStat
                :value="stats.overall_percentage"
                suffix="%"
                label="Overall progress"
            />
            <DashboardStat
                :value="certificates_count"
                label="Certificates"
                :tone="certificates_count > 0 ? 'positive' : 'default'"
            />
        </div>

        <!-- ─── DUE SOON ───────────────────────────────────── -->
        <section v-if="due_soon.length" class="mb-8">
            <h2 class="mb-3 text-lg font-bold text-navy">Deadlines</h2>

            <div class="card divide-y divide-line overflow-hidden">
                <Link
                    v-for="item in due_soon"
                    :key="item.course_slug"
                    :href="route('courses.show', item.course_slug)"
                    class="flex items-center gap-3 px-5 py-3.5 no-underline transition-colors hover:bg-surface-alt"
                >
                    <span class="flex-1 text-sm font-medium text-ink">{{ item.course_title }}</span>
                    <StatusPill
                        :label="item.is_overdue ? 'Overdue' : `Due ${formatDate(item.due_at)}`"
                        :tone="item.is_overdue ? 'negative' : 'warning'"
                    />
                </Link>
            </div>
        </section>

        <!-- ─── COURSES ─────────────────────────────────────── -->
        <section v-if="courses.length" class="mb-8">
            <template v-if="inProgress.length">
                <h2 class="mb-3 text-lg font-bold text-navy">Continue learning</h2>
                <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <CourseCard v-for="course in inProgress" :key="course.id" :course="course" />
                </div>
            </template>

            <template v-if="notStarted.length">
                <h2 class="mb-3 text-lg font-bold text-navy">
                    {{ is_new_starter ? 'Your onboarding path' : 'Not started yet' }}
                </h2>
                <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <CourseCard v-for="course in notStarted" :key="course.id" :course="course" />
                </div>
            </template>

            <template v-if="finished.length">
                <h2 class="mb-3 text-lg font-bold text-navy">Completed</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <CourseCard v-for="course in finished" :key="course.id" :course="course" />
                </div>
            </template>
        </section>

        <EmptyState
            v-else
            class="mb-8"
            title="No training assigned yet"
            description="When your team lead assigns you a course it will appear here. In the meantime you can browse what is available."
        >
            <Link
                :href="route('courses.index')"
                class="inline-block rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white no-underline transition-colors hover:bg-brand-hover"
            >
                Browse courses
            </Link>
        </EmptyState>

        <!-- ─── RESULTS + RECOMMENDED ──────────────────────── -->
        <div
            v-if="recent_results.length || recommended.length"
            class="grid grid-cols-1 gap-6 lg:grid-cols-2"
        >
            <section v-if="recent_results.length">
                <h2 class="mb-3 text-lg font-bold text-navy">Recent quiz results</h2>

                <div class="card divide-y divide-line overflow-hidden">
                    <Link
                        v-for="result in recent_results"
                        :key="result.id"
                        :href="route('attempts.show', result.id)"
                        class="flex items-center gap-3 px-5 py-3.5 no-underline transition-colors hover:bg-surface-alt"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="truncate text-sm font-medium text-ink">
                                {{ result.quiz_title }}
                            </div>
                            <div class="truncate text-xs text-ink-dis">
                                {{ result.course_title }}
                            </div>
                        </div>
                        <span
                            class="text-sm font-bold"
                            :class="result.passed ? 'text-ok' : 'text-negative'"
                        >
                            {{ Math.round(result.score) }}%
                        </span>
                        <StatusPill
                            :label="result.passed ? 'Passed' : 'Failed'"
                            :tone="result.passed ? 'positive' : 'negative'"
                        />
                    </Link>
                </div>
            </section>

            <section v-if="recommended.length">
                <h2 class="mb-3 text-lg font-bold text-navy">Also available</h2>

                <div class="card divide-y divide-line overflow-hidden">
                    <Link
                        v-for="course in recommended"
                        :key="course.slug"
                        :href="route('courses.show', course.slug)"
                        class="block px-5 py-3.5 no-underline transition-colors hover:bg-surface-alt"
                    >
                        <span v-if="course.category" class="chip mb-1 bg-brand-soft text-brand">
                            {{ course.category }}
                        </span>
                        <div class="text-sm font-semibold text-navy">{{ course.title }}</div>
                        <p class="mt-0.5 line-clamp-2 text-xs text-ink-sec">{{ course.summary }}</p>
                    </Link>
                </div>
            </section>
        </div>
    </EmployeeLayout>
</template>

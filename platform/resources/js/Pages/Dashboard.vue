<script setup>
/**
 * The prototype's index.html, driven by data.
 *
 * Hero, stats bar and card grid are reproduced faithfully — including the two
 * offset radial glows behind the hero and the stats bar's -40px overlap onto
 * the cards. The four stat cells now hold real aggregates instead of the
 * hard-coded 3 / 2 / 4 / 7.
 */
import { Head, Link, usePage } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import DashboardStat from '@/Components/DashboardStat.vue';
import EmptyState from '@/Components/EmptyState.vue';
import StatusPill from '@/Components/StatusPill.vue';

defineProps({
    stats: { type: Object, required: true },
    courses: { type: Array, default: () => [] },
    due_soon: { type: Array, default: () => [] },
    recent_results: { type: Array, default: () => [] },
    recommended: { type: Array, default: () => [] },
    certificates_count: { type: Number, default: 0 },
});

const user = usePage().props.auth.user;

const formatDate = (iso) =>
    iso ? new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) : '';
</script>

<template>
    <Head title="Dashboard" />

    <EmployeeLayout>
        <!-- ─── HERO ────────────────────────────────────────── -->
        <section class="relative overflow-hidden px-6 pt-20 pb-16 text-center">
            <div
                class="pointer-events-none absolute -top-30 left-1/2 h-[800px] w-[800px] -translate-x-1/2 bg-[radial-gradient(circle,rgba(2,132,199,.1)_0%,transparent_65%)]"
            ></div>
            <div
                class="pointer-events-none absolute top-5 left-[40%] h-[600px] w-[600px] -translate-x-1/2 bg-[radial-gradient(circle,rgba(234,179,8,.08)_0%,transparent_60%)]"
            ></div>

            <div class="relative z-1 mx-auto max-w-[720px]">
                <div
                    class="mono-label mb-6 inline-flex items-center gap-2 rounded-full border border-primary bg-primary-bg px-[18px] py-1.5 text-[10px] tracking-[2.5px] text-primary-hover"
                >
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary"></span>
                    Onboarding Portal
                </div>

                <h1 class="mb-4 text-[clamp(32px,5vw,52px)] leading-[1.1] font-extrabold tracking-tight">
                    Welcome back,
                    <span
                        class="bg-gradient-to-br from-primary to-warning bg-clip-text text-transparent"
                    >
                        {{ user?.name?.split(' ')[0] }}
                    </span>
                </h1>

                <p class="mx-auto mb-9 max-w-[560px] text-[clamp(15px,2vw,18px)] leading-relaxed text-ink-sec">
                    Your centralized command center for support onboarding — track progress,
                    diagnose issues, and master the skills that matter.
                </p>
            </div>
        </section>

        <!-- ─── STATS BAR ──────────────────────────────────── -->
        <div class="mx-auto -mt-10 mb-15 max-w-[1200px] px-6">
            <div
                class="flex flex-wrap justify-center gap-12 rounded-[14px] border border-glass-border bg-glass-bg backdrop-blur-md px-8 py-7 shadow-lg transition-all duration-300 hover:scale-[1.01] hover:shadow-xl"
            >
                <DashboardStat :value="stats.assigned" label="Assigned" />
                <DashboardStat :value="stats.in_progress" label="In Progress" />
                <DashboardStat :value="stats.completed" label="Completed" />
                <DashboardStat :value="stats.overall_percentage" suffix="%" label="Overall" />
                <DashboardStat :value="certificates_count" label="Certificates" />
            </div>
        </div>

        <!-- ─── DUE SOON ───────────────────────────────────── -->
        <div v-if="due_soon.length" class="mx-auto mb-10 max-w-[1200px] px-6">
            <h2 class="mono-label mb-3 text-[11px] tracking-[2.5px] text-ink-sec">
                Upcoming &amp; overdue
            </h2>
            <div class="overflow-hidden rounded-lg border border-glass-border bg-glass-bg backdrop-blur-md shadow-md">
                <Link
                    v-for="item in due_soon"
                    :key="item.course_slug"
                    :href="route('courses.show', item.course_slug)"
                    class="group flex items-center gap-3 border-b border-glass-border px-4 py-3 no-underline last:border-b-0 hover:bg-white/40 dark:hover:bg-slate-800/40 transition-colors duration-250"
                >
                    <span class="flex-1 text-[13px] text-ink transition-transform duration-250 group-hover:translate-x-1">{{ item.course_title }}</span>
                    <StatusPill
                        :label="item.is_overdue ? 'Overdue' : 'Due ' + formatDate(item.due_at)"
                        :tone="item.is_overdue ? 'negative' : 'warning'"
                    />
                </Link>
            </div>
        </div>

        <!-- ─── COURSES ─────────────────────────────────────── -->
        <div class="mx-auto max-w-[1200px] px-6 pb-20">
            <h2 class="mono-label mb-4 text-[11px] tracking-[2.5px] text-ink-sec">
                Your training
            </h2>

            <div v-if="courses.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <CourseCard
                    v-for="(course, i) in courses"
                    :key="course.id"
                    :course="course"
                    :index="i"
                />
            </div>

            <EmptyState
                v-else
                icon="📋"
                title="No training assigned yet"
                description="When an administrator assigns you a course it will appear here. In the meantime you can browse what is available."
            >
                <Link
                    :href="route('courses.index')"
                    class="mono-label rounded-[5px] border border-primary bg-primary px-4 py-2 text-[11px] tracking-[1.5px] text-on-accent no-underline"
                >
                    Browse courses
                </Link>
            </EmptyState>
        </div>

        <!-- ─── RECENT RESULTS + RECOMMENDED ───────────────── -->
        <div
            v-if="recent_results.length || recommended.length"
            class="mx-auto grid max-w-[1200px] grid-cols-1 gap-6 px-6 pb-20 lg:grid-cols-2"
        >
            <div v-if="recent_results.length">
                <h2 class="mono-label mb-3 text-[11px] tracking-[2.5px] text-ink-sec">
                    Recent quiz results
                </h2>
                <div class="overflow-hidden rounded-lg border border-glass-border bg-glass-bg backdrop-blur-md shadow-md">
                    <Link
                        v-for="result in recent_results"
                        :key="result.id"
                        :href="route('attempts.show', result.id)"
                        class="group flex items-center gap-3 border-b border-glass-border px-4 py-3 no-underline last:border-b-0 hover:bg-white/40 dark:hover:bg-slate-800/40 transition-colors duration-250"
                    >
                        <div class="min-w-0 flex-1 transition-transform duration-250 group-hover:translate-x-1">
                            <div class="truncate text-[13px] text-ink">{{ result.quiz_title }}</div>
                            <div class="mono-label text-[9px] tracking-[1px] text-ink-dis">
                                {{ result.course_title }}
                            </div>
                        </div>
                        <span
                            class="font-mono text-sm font-bold"
                            :class="result.passed ? 'text-positive' : 'text-negative'"
                        >
                            {{ Math.round(result.score) }}%
                        </span>
                        <StatusPill
                            :label="result.passed ? 'Passed' : 'Failed'"
                            :tone="result.passed ? 'positive' : 'negative'"
                        />
                    </Link>
                </div>
            </div>

            <div v-if="recommended.length">
                <h2 class="mono-label mb-3 text-[11px] tracking-[2.5px] text-ink-sec">
                    Recommended for you
                </h2>
                <div class="overflow-hidden rounded-lg border border-glass-border bg-glass-bg backdrop-blur-md shadow-md">
                    <Link
                        v-for="course in recommended"
                        :key="course.slug"
                        :href="route('courses.show', course.slug)"
                        class="group block border-b border-glass-border px-4 py-3 no-underline last:border-b-0 hover:bg-white/40 dark:hover:bg-slate-800/40 transition-colors duration-250"
                    >
                        <div class="mono-label text-[9px] tracking-[2px] text-ink-dis transition-transform duration-250 group-hover:translate-x-1">
                            {{ course.category }}
                        </div>
                        <div class="text-[13px] font-bold text-ink transition-transform duration-250 group-hover:translate-x-1">{{ course.title }}</div>
                        <p class="mt-1 line-clamp-2 text-xs text-ink-sec transition-transform duration-250 group-hover:translate-x-1">{{ course.summary }}</p>
                    </Link>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>

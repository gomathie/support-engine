<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    course: { type: Object, required: true },
    quiz: { type: Object, required: true },
    history: { type: Array, default: () => [] },
    state: { type: Object, required: true },
});

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : '—');

function start() {
    router.post(route('quizzes.start', [props.course.slug, props.quiz.id]));
}
</script>

<template>
    <Head :title="quiz.title" />

    <EmployeeLayout>
        <div class="mx-auto max-w-3xl">
            <Link
                :href="route('courses.show', course.slug)"
                class="mb-5 inline-block text-sm font-medium text-ink-sec no-underline hover:text-brand"
            >
                ← {{ course.title }}
            </Link>

            <span class="chip mb-3 bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
                Assessment
            </span>

            <h1 class="mb-2 text-2xl font-extrabold text-navy">{{ quiz.title }}</h1>

            <p v-if="quiz.description" class="mb-7 text-base leading-relaxed text-ink-sec">
                {{ quiz.description }}
            </p>

            <!-- ─── RULES ───────────────────────────────────── -->
            <div class="mb-7 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="card px-4 py-3.5 text-center">
                    <div class="text-xl font-extrabold text-navy">{{ quiz.question_count }}</div>
                    <div class="mt-1 text-xs text-ink-sec">Questions</div>
                </div>
                <div class="card px-4 py-3.5 text-center">
                    <div class="text-xl font-extrabold text-navy">{{ quiz.passing_score }}%</div>
                    <div class="mt-1 text-xs text-ink-sec">To pass</div>
                </div>
                <div class="card px-4 py-3.5 text-center">
                    <div class="text-xl font-extrabold text-navy">
                        {{ state.attempts_remaining ?? '∞' }}
                    </div>
                    <div class="mt-1 text-xs text-ink-sec">Attempts left</div>
                </div>
                <div class="card px-4 py-3.5 text-center">
                    <div class="text-xl font-extrabold text-navy">
                        {{ quiz.time_limit_minutes ? `${quiz.time_limit_minutes}m` : '—' }}
                    </div>
                    <div class="mt-1 text-xs text-ink-sec">Time limit</div>
                </div>
            </div>

            <div
                v-if="state.passed"
                class="mb-7 rounded-xl border border-ok/40 bg-positive-bg px-5 py-4 text-sm font-medium text-ok"
            >
                ✓ You have already passed this assessment.
            </div>

            <button
                v-if="state.can_attempt"
                type="button"
                class="mb-9 w-full cursor-pointer rounded-lg bg-brand py-3.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover"
                @click="start"
            >
                {{
                    state.resume_attempt_id
                        ? 'Resume attempt'
                        : state.attempts_used
                          ? 'Retake assessment'
                          : 'Begin assessment'
                }}
            </button>

            <p v-else class="mb-9 text-sm text-ink-dis italic">
                No attempts remaining. Speak to your trainer if you need another.
            </p>

            <!-- ─── HISTORY ─────────────────────────────────── -->
            <section v-if="history.length">
                <h2 class="mb-3 text-lg font-bold text-navy">Your attempts</h2>

                <div class="card divide-y divide-line overflow-hidden">
                    <Link
                        v-for="attempt in history"
                        :key="attempt.id"
                        :href="route('attempts.show', attempt.id)"
                        class="flex items-center gap-3 px-5 py-3.5 no-underline transition-colors hover:bg-surface-alt"
                    >
                        <span class="text-sm font-medium text-ink-dis">
                            #{{ attempt.attempt_number }}
                        </span>
                        <span class="flex-1 text-sm text-ink-sec">
                            {{ formatDate(attempt.completed_at) }}
                        </span>
                        <span
                            class="text-sm font-bold"
                            :class="attempt.passed ? 'text-ok' : 'text-negative'"
                        >
                            {{ Math.round(attempt.score) }}%
                        </span>
                        <StatusPill
                            :label="attempt.passed ? 'Passed' : 'Failed'"
                            :tone="attempt.passed ? 'positive' : 'negative'"
                        />
                    </Link>
                </div>
            </section>
        </div>
    </EmployeeLayout>
</template>

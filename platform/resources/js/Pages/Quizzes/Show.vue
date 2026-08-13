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
        <div class="mx-auto max-w-[720px] px-5 py-10">
            <Link
                :href="route('courses.show', course.slug)"
                class="mono-label mb-4 inline-block text-[10px] tracking-[2px] text-primary no-underline"
            >
                ← {{ course.title }}
            </Link>

            <h1 class="mb-2 text-xl font-bold">{{ quiz.title }}</h1>
            <p v-if="quiz.description" class="mb-6 text-[13px] leading-relaxed text-ink-sec">
                {{ quiz.description }}
            </p>

            <!-- ─── RULES ───────────────────────────────────── -->
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg border border-line bg-surface px-3 py-2.5 text-center">
                    <div class="font-mono text-lg font-bold text-ink">{{ quiz.question_count }}</div>
                    <div class="mono-label text-[9px] tracking-[1px] text-ink-sec">Questions</div>
                </div>
                <div class="rounded-lg border border-line bg-surface px-3 py-2.5 text-center">
                    <div class="font-mono text-lg font-bold text-ink">{{ quiz.passing_score }}%</div>
                    <div class="mono-label text-[9px] tracking-[1px] text-ink-sec">To pass</div>
                </div>
                <div class="rounded-lg border border-line bg-surface px-3 py-2.5 text-center">
                    <div class="font-mono text-lg font-bold text-ink">
                        {{ state.attempts_remaining ?? '∞' }}
                    </div>
                    <div class="mono-label text-[9px] tracking-[1px] text-ink-sec">Attempts left</div>
                </div>
                <div class="rounded-lg border border-line bg-surface px-3 py-2.5 text-center">
                    <div class="font-mono text-lg font-bold text-ink">
                        {{ quiz.time_limit_minutes ? quiz.time_limit_minutes + 'm' : '—' }}
                    </div>
                    <div class="mono-label text-[9px] tracking-[1px] text-ink-sec">Time limit</div>
                </div>
            </div>

            <div
                v-if="state.passed"
                class="mb-6 rounded-lg border border-positive bg-positive-bg px-4 py-3 text-[13px] text-positive"
            >
                You have already passed this assessment.
            </div>

            <button
                v-if="state.can_attempt"
                type="button"
                class="mono-label mb-8 w-full cursor-pointer rounded-[5px] border border-primary bg-primary py-3 text-[11px] font-bold tracking-[1.5px] text-on-accent transition-colors hover:bg-primary-hover"
                @click="start"
            >
                {{ state.resume_attempt_id ? 'Resume attempt' : state.attempts_used ? 'Retake' : 'Begin' }}
            </button>

            <p v-else class="mb-8 text-[13px] text-ink-dis italic">
                No attempts remaining. Speak to your trainer if you need another.
            </p>

            <!-- ─── HISTORY ─────────────────────────────────── -->
            <div v-if="history.length">
                <h2 class="mono-label mb-3 text-[10px] tracking-[2px] text-ink-sec">
                    Your attempts
                </h2>

                <div class="overflow-hidden rounded-lg border border-line bg-surface">
                    <Link
                        v-for="attempt in history"
                        :key="attempt.id"
                        :href="route('attempts.show', attempt.id)"
                        class="flex items-center gap-3 border-b border-line px-4 py-3 no-underline last:border-b-0 hover:bg-surface-alt"
                    >
                        <span class="mono-label text-[10px] tracking-[1px] text-ink-dis">
                            #{{ attempt.attempt_number }}
                        </span>
                        <span class="flex-1 text-xs text-ink-sec">
                            {{ formatDate(attempt.completed_at) }}
                        </span>
                        <span
                            class="font-mono text-sm font-bold"
                            :class="attempt.passed ? 'text-positive' : 'text-negative'"
                        >
                            {{ Math.round(attempt.score) }}%
                        </span>
                        <StatusPill
                            :label="attempt.passed ? 'Passed' : 'Failed'"
                            :tone="attempt.passed ? 'positive' : 'negative'"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>

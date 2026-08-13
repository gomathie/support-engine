<script setup>
/**
 * The marked paper. This is the only screen that ever shows which option was
 * correct, and it is rendered from an attempt that has already been graded.
 */
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

defineProps({
    course: { type: Object, required: true },
    quiz: { type: Object, required: true },
    attempt: { type: Object, required: true },
    answers: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="`${quiz.title} — results`" />

    <EmployeeLayout>
        <div class="mx-auto max-w-[760px] px-5 py-10">
            <Link
                :href="route('courses.show', course.slug)"
                class="mono-label mb-4 inline-block text-[10px] tracking-[2px] text-primary no-underline"
            >
                ← {{ course.title }}
            </Link>

            <!-- ─── SCORE ───────────────────────────────────── -->
            <div
                class="mb-8 rounded-2xl border px-6 py-8 text-center"
                :class="
                    attempt.passed
                        ? 'border-positive bg-positive-bg'
                        : 'border-negative bg-negative-bg'
                "
            >
                <div
                    class="font-mono text-5xl font-bold tracking-tight"
                    :class="attempt.passed ? 'text-positive' : 'text-negative'"
                >
                    {{ Math.round(attempt.score) }}%
                </div>

                <div class="mt-2 mb-3">
                    <StatusPill
                        :label="attempt.passed ? 'Passed' : 'Not passed'"
                        :tone="attempt.passed ? 'positive' : 'negative'"
                    />
                </div>

                <p class="mono-label text-[10px] tracking-[1.5px] text-ink-sec">
                    {{ attempt.points_earned }} of {{ attempt.points_possible }} points ·
                    attempt {{ attempt.attempt_number }} · pass mark {{ attempt.passing_score }}%
                </p>
            </div>

            <!-- ─── FEEDBACK ────────────────────────────────── -->
            <div v-if="quiz.show_feedback && answers.length" class="flex flex-col gap-4">
                <h2 class="mono-label text-[10px] tracking-[2px] text-ink-sec">Your answers</h2>

                <div
                    v-for="(answer, i) in answers"
                    :key="answer.question_id"
                    class="rounded-lg border bg-surface px-5 py-4"
                    :class="answer.is_correct ? 'border-positive' : 'border-negative'"
                >
                    <div class="mb-3 flex items-start gap-3">
                        <span
                            class="mt-0.5 min-w-4 font-mono text-[11px] font-bold"
                            :class="answer.is_correct ? 'text-positive' : 'text-negative'"
                        >
                            {{ i + 1 }}
                        </span>
                        <p class="flex-1 text-[13px] leading-relaxed text-ink">
                            {{ answer.prompt }}
                        </p>
                        <span
                            class="mono-label text-[9px] tracking-[1px]"
                            :class="answer.is_correct ? 'text-positive' : 'text-negative'"
                        >
                            {{ answer.points_awarded }}/{{ answer.points }}
                        </span>
                    </div>

                    <!-- Choice questions -->
                    <ul v-if="answer.options.length" class="flex flex-col gap-1.5 pl-7">
                        <li
                            v-for="(option, j) in answer.options"
                            :key="j"
                            class="flex items-start gap-2.5 rounded-md border px-3 py-2 text-[13px]"
                            :class="[
                                option.is_correct
                                    ? 'border-positive bg-positive-bg'
                                    : option.selected
                                      ? 'border-negative bg-negative-bg'
                                      : 'border-line',
                            ]"
                        >
                            <span class="mono-label mt-px text-[9px] tracking-[1px]">
                                <template v-if="option.is_correct && option.selected">✓</template>
                                <template v-else-if="option.is_correct">·</template>
                                <template v-else-if="option.selected">✕</template>
                                <template v-else>&nbsp;</template>
                            </span>
                            <span class="text-ink">{{ option.label }}</span>
                        </li>
                    </ul>

                    <!-- Short answer -->
                    <div v-else class="pl-7 text-[13px]">
                        <p class="text-ink">
                            <span class="mono-label mr-2 text-[9px] tracking-[1px] text-ink-dis">
                                You wrote
                            </span>
                            {{ answer.text_answer || '—' }}
                        </p>
                        <p v-if="!answer.is_correct" class="mt-1 text-ink-sec">
                            <span class="mono-label mr-2 text-[9px] tracking-[1px] text-ink-dis">
                                Accepted
                            </span>
                            {{ answer.accepted_answers.join(' / ') }}
                        </p>
                    </div>

                    <p
                        v-if="answer.explanation"
                        class="mt-3 border-t border-line pt-2.5 pl-7 text-xs leading-relaxed text-primary"
                    >
                        {{ answer.explanation }}
                    </p>
                </div>
            </div>

            <p v-else-if="!quiz.show_feedback" class="text-[13px] text-ink-dis italic">
                Per-question feedback is not enabled for this assessment.
            </p>

            <div class="mt-8">
                <Link
                    :href="route('quizzes.show', [course.slug, quiz.id])"
                    class="mono-label inline-block rounded-[5px] border border-line px-4 py-2.5 text-[11px] tracking-[1.5px] text-ink-sec no-underline transition-colors hover:border-primary hover:text-primary"
                >
                    Back to quiz
                </Link>
            </div>
        </div>
    </EmployeeLayout>
</template>

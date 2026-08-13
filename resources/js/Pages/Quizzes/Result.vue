<script setup>
/**
 * The marked paper. The only screen that ever shows which option was correct,
 * rendered from an attempt that has already been graded.
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
        <div class="mx-auto max-w-3xl">
            <Link
                :href="route('courses.show', course.slug)"
                class="mb-5 inline-block text-sm font-medium text-ink-sec no-underline hover:text-brand"
            >
                ← {{ course.title }}
            </Link>

            <!-- ─── AWAITING REVIEW ─────────────────────────── -->
            <div v-if="attempt.awaiting_review" class="card mb-8 border-warning-dim/40 p-9 text-center">
                <div class="mb-3 text-4xl">⏳</div>

                <h1 class="mb-2 text-xl font-extrabold text-navy">With an examiner</h1>

                <p class="mx-auto mb-4 max-w-md text-sm leading-relaxed text-ink-sec">
                    Your written answers are being marked by hand.
                    {{ attempt.outstanding }}
                    {{ attempt.outstanding === 1 ? 'answer is' : 'answers are' }} still to be read.
                    You will see your result here once they are done.
                </p>

                <StatusPill label="Awaiting review" tone="warning" />

                <p class="mt-4 text-sm text-ink-dis">
                    Submitted
                    {{ attempt.completed_at ? new Date(attempt.completed_at).toLocaleString() : '' }}
                    · attempt {{ attempt.attempt_number }}
                </p>
            </div>

            <!-- ─── SCORE ───────────────────────────────────── -->
            <div
                v-else
                class="card mb-8 p-9 text-center"
                :class="attempt.passed ? 'border-ok/40' : 'border-negative/40'"
            >
                <div
                    class="text-5xl font-extrabold tracking-tight"
                    :class="attempt.passed ? 'text-ok' : 'text-negative'"
                >
                    {{ Math.round(attempt.score) }}%
                </div>

                <div class="mt-3 mb-4">
                    <StatusPill
                        :label="attempt.passed ? 'Passed' : 'Not passed'"
                        :tone="attempt.passed ? 'positive' : 'negative'"
                    />
                </div>

                <p class="text-sm text-ink-sec">
                    {{ attempt.points_earned }} of {{ attempt.points_possible }} points ·
                    attempt {{ attempt.attempt_number }} · pass mark {{ attempt.passing_score }}%
                </p>

                <p v-if="attempt.reviewed_at" class="mt-1 text-xs text-ink-dis">
                    Written answers marked by an examiner.
                </p>
            </div>

            <!-- ─── FEEDBACK ────────────────────────────────── -->
            <div v-if="quiz.show_feedback && answers.length" class="flex flex-col gap-4">
                <h2 class="text-lg font-bold text-navy">Your answers</h2>

                <div
                    v-for="(answer, i) in answers"
                    :key="answer.question_id"
                    class="card p-6"
                    :class="answer.is_correct ? 'border-ok/40' : 'border-negative/40'"
                >
                    <div class="mb-4 flex items-start gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
                            :class="
                                answer.awaiting_review
                                    ? 'bg-warning-dim'
                                    : answer.is_correct
                                      ? 'bg-ok'
                                      : 'bg-negative'
                            "
                        >
                            {{ answer.awaiting_review ? '…' : answer.is_correct ? '✓' : '✕' }}
                        </span>

                        <p class="flex-1 text-base leading-relaxed font-medium whitespace-pre-line text-navy">
                            {{ answer.prompt }}
                        </p>

                        <span
                            class="shrink-0 text-sm font-semibold"
                            :class="
                                answer.awaiting_review
                                    ? 'text-ink-dis'
                                    : answer.is_correct
                                      ? 'text-ok'
                                      : 'text-negative'
                            "
                        >
                            <template v-if="answer.awaiting_review">— /{{ answer.points }}</template>
                            <template v-else>{{ answer.points_awarded }}/{{ answer.points }}</template>
                        </span>
                    </div>

                    <!-- Choice questions -->
                    <ul v-if="answer.options.length" class="flex flex-col gap-2 pl-9">
                        <li
                            v-for="(option, j) in answer.options"
                            :key="j"
                            class="flex items-start gap-2.5 rounded-xl border px-4 py-2.5 text-sm"
                            :class="
                                option.is_correct
                                    ? 'border-ok/40 bg-positive-bg'
                                    : option.selected
                                      ? 'border-negative/40 bg-negative-bg'
                                      : 'border-line'
                            "
                        >
                            <span class="w-4 shrink-0 font-bold">
                                <template v-if="option.is_correct && option.selected">✓</template>
                                <template v-else-if="option.is_correct">·</template>
                                <template v-else-if="option.selected">✕</template>
                            </span>
                            <span class="text-ink">{{ option.label }}</span>
                        </li>
                    </ul>

                    <!-- Written answer: what they wrote, plus the examiner's note. -->
                    <div v-else-if="answer.requires_review" class="space-y-3 pl-9 text-sm">
                        <div>
                            <p class="mb-1 text-xs font-semibold text-ink-dis uppercase">
                                Your answer
                            </p>
                            <p class="rounded-xl bg-surface-alt px-4 py-3 leading-relaxed whitespace-pre-line text-ink">
                                {{ answer.text_answer || 'No answer submitted.' }}
                            </p>
                        </div>

                        <div v-if="answer.grader_feedback">
                            <p class="mb-1 text-xs font-semibold text-ink-dis uppercase">
                                Examiner feedback
                            </p>
                            <p class="rounded-xl bg-brand-soft px-4 py-3 leading-relaxed whitespace-pre-line text-brand">
                                {{ answer.grader_feedback }}
                            </p>
                        </div>

                        <p v-else-if="answer.awaiting_review" class="text-ink-dis italic">
                            Not yet marked.
                        </p>
                    </div>

                    <!-- Short answer -->
                    <div v-else class="space-y-1 pl-9 text-sm">
                        <p class="text-ink">
                            <span class="mr-2 text-xs font-semibold text-ink-dis uppercase">
                                You wrote
                            </span>
                            {{ answer.text_answer || '—' }}
                        </p>
                        <p v-if="!answer.is_correct" class="text-ink-sec">
                            <span class="mr-2 text-xs font-semibold text-ink-dis uppercase">
                                Accepted
                            </span>
                            {{ answer.accepted_answers.join(' / ') }}
                        </p>
                    </div>

                    <p
                        v-if="answer.explanation"
                        class="mt-4 rounded-xl bg-brand-soft px-4 py-3 text-sm leading-relaxed text-brand"
                    >
                        {{ answer.explanation }}
                    </p>
                </div>
            </div>

            <p v-else-if="!quiz.show_feedback" class="text-sm text-ink-dis italic">
                Per-question feedback is not enabled for this assessment.
            </p>

            <div class="mt-8">
                <Link
                    :href="route('quizzes.show', [course.slug, quiz.id])"
                    class="inline-block rounded-lg border border-line px-5 py-2.5 text-sm font-semibold text-ink-sec no-underline transition-colors hover:border-brand hover:text-brand"
                >
                    Back to assessment
                </Link>
            </div>
        </div>
    </EmployeeLayout>
</template>

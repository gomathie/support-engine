<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import QuizQuestion from '@/Components/QuizQuestion.vue';

const props = defineProps({
    course: { type: Object, required: true },
    quiz: { type: Object, required: true },
    attempt: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
});

const answers = reactive(
    Object.fromEntries(props.questions.map((q) => [q.id, { option_ids: [], text: null }])),
);

const form = useForm({
    attempt_id: props.attempt.id,
    answers: [],
});

const answeredCount = computed(
    () =>
        Object.values(answers).filter(
            (a) => (a.option_ids?.length ?? 0) > 0 || (a.text ?? '').trim() !== '',
        ).length,
);

const answeredPercent = computed(() =>
    props.questions.length ? (answeredCount.value / props.questions.length) * 100 : 0,
);

// ─── COUNTDOWN ────────────────────────────────────────────
// A convenience, not a control. The server re-checks the deadline on
// submission, so closing the tab to stop the clock achieves nothing.
const remaining = ref(null);
let ticker = null;

function tick() {
    if (!props.attempt.expires_at) return;

    const ms = new Date(props.attempt.expires_at) - new Date();
    remaining.value = Math.max(0, Math.floor(ms / 1000));

    if (remaining.value === 0) submit();
}

const clock = computed(() => {
    if (remaining.value === null) return null;
    const m = String(Math.floor(remaining.value / 60)).padStart(2, '0');
    const s = String(remaining.value % 60).padStart(2, '0');
    return `${m}:${s}`;
});

onMounted(() => {
    if (props.attempt.expires_at) {
        tick();
        ticker = setInterval(tick, 1000);
    }
});

onUnmounted(() => clearInterval(ticker));

function submit() {
    clearInterval(ticker);

    form.answers = props.questions.map((question) => ({
        question_id: question.id,
        option_ids: answers[question.id].option_ids ?? [],
        text: answers[question.id].text ?? null,
    }));

    form.post(route('quizzes.submit', [props.course.slug, props.quiz.id]));
}
</script>

<template>
    <Head :title="quiz.title" />

    <EmployeeLayout>
        <div class="mx-auto max-w-3xl">
            <!-- Progress bar sticks under the site header while scrolling. -->
            <div class="sticky top-16 z-10 -mx-5 mb-6 border-b border-line bg-canvas px-5 py-3">
                <div class="mb-2 flex items-center gap-3">
                    <span class="flex-1 truncate text-sm font-bold text-navy">
                        {{ quiz.title }}
                    </span>
                    <span class="text-sm text-ink-sec">
                        {{ answeredCount }}/{{ questions.length }} answered
                    </span>
                    <span
                        v-if="clock"
                        class="rounded-lg px-2.5 py-1 text-sm font-bold tabular-nums"
                        :class="
                            remaining < 60
                                ? 'bg-negative-bg text-negative'
                                : 'bg-warning-bg text-warning-dim dark:text-warning'
                        "
                    >
                        {{ clock }}
                    </span>
                </div>

                <div class="h-1.5 overflow-hidden rounded-full bg-surface-alt">
                    <div
                        class="h-full rounded-full bg-brand transition-[width] duration-300"
                        :style="{ width: `${answeredPercent}%` }"
                    ></div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="flex flex-col gap-4">
                    <QuizQuestion
                        v-for="(question, i) in questions"
                        :key="question.id"
                        v-model="answers[question.id]"
                        :question="question"
                        :index="i"
                    />
                </div>

                <p v-if="form.errors.quiz" class="mt-4 text-sm text-negative">
                    {{ form.errors.quiz }}
                </p>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-8 w-full cursor-pointer rounded-lg bg-brand py-3.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover disabled:opacity-60"
                >
                    {{ form.processing ? 'Submitting…' : 'Submit answers' }}
                </button>

                <p
                    v-if="answeredCount < questions.length"
                    class="mt-3 text-center text-sm text-ink-dis italic"
                >
                    {{ questions.length - answeredCount }} unanswered — these will score zero.
                </p>
            </form>
        </div>
    </EmployeeLayout>
</template>

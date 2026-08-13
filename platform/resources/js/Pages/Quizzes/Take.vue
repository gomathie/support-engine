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

// ─── COUNTDOWN ────────────────────────────────────────────
// A convenience, not a control. The server re-checks the deadline when the
// attempt is submitted, so closing the tab to stop the clock achieves nothing.
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
        <div class="sticky top-[54px] z-30 border-b border-line bg-nav backdrop-blur-[6px]">
            <div class="mx-auto flex max-w-[760px] items-center gap-4 px-5 py-2.5">
                <span class="mono-label text-[11px] tracking-[2.5px] text-primary">
                    {{ quiz.title }}
                </span>
                <div class="flex-1"></div>
                <span class="mono-label text-[10px] tracking-[1px] text-ink-sec">
                    {{ answeredCount }}/{{ questions.length }} answered
                </span>
                <span
                    v-if="clock"
                    class="font-mono text-sm font-bold"
                    :class="remaining < 60 ? 'text-negative' : 'text-warning'"
                >
                    {{ clock }}
                </span>
            </div>
        </div>

        <form class="mx-auto max-w-[760px] px-5 py-8" @submit.prevent="submit">
            <div class="flex flex-col gap-4">
                <QuizQuestion
                    v-for="(question, i) in questions"
                    :key="question.id"
                    v-model="answers[question.id]"
                    :question="question"
                    :index="i"
                />
            </div>

            <p v-if="form.errors.quiz" class="mt-4 text-xs text-negative">{{ form.errors.quiz }}</p>

            <div class="mt-8 flex items-center gap-4">
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mono-label flex-1 cursor-pointer rounded-[5px] border border-primary bg-primary py-3 text-[11px] font-bold tracking-[1.5px] text-on-accent transition-colors hover:bg-primary-hover disabled:opacity-60"
                >
                    {{ form.processing ? 'Submitting…' : 'Submit answers' }}
                </button>
            </div>

            <p
                v-if="answeredCount < questions.length"
                class="mt-3 text-center text-xs text-ink-dis italic"
            >
                {{ questions.length - answeredCount }} unanswered — these will score zero.
            </p>
        </form>
    </EmployeeLayout>
</template>

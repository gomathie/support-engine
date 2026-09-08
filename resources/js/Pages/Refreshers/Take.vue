<script setup>
/**
 * A refresher: five questions, thirty or ninety days after a level was awarded.
 *
 * Deliberately calmer than the quiz screen. There is no clock, no attempt
 * counter and no pass mark, because there is nothing at stake — the level is
 * already held and a poor score takes nothing away. What it measures is whether
 * the training stuck, and a trainee who thinks they are being re-examined will
 * revise first, which measures nothing.
 */
import { computed, reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import QuizQuestion from '@/Components/QuizQuestion.vue';

const props = defineProps({
    refresher: { type: Object, required: true },
    questions: { type: Array, default: () => [] },
});

const answers = reactive(
    Object.fromEntries(props.questions.map((q) => [q.id, { option_ids: [], text: null }])),
);

const form = useForm({ answers: [] });

const answeredCount = computed(
    () => Object.values(answers).filter((a) => (a.option_ids?.length ?? 0) > 0).length,
);

function submit() {
    form.answers = props.questions.map((question) => ({
        question_id: question.id,
        option_ids: answers[question.id].option_ids ?? [],
    }));

    form.post(route('refreshers.submit', props.refresher.id), { preserveScroll: false });
}
</script>

<template>
    <Head :title="refresher.label" />

    <EmployeeLayout>
        <article class="mx-auto max-w-3xl">
            <!-- ─── HEADER ──────────────────────────────────── -->
            <div class="card mb-6 p-6">
                <p class="mb-1 text-sm font-medium text-brand">
                    {{ refresher.level }}<span v-if="refresher.area"> · {{ refresher.area }}</span>
                </p>

                <h1 class="mb-3 text-2xl font-extrabold text-navy">{{ refresher.label }}</h1>

                <p class="text-sm leading-relaxed text-ink-sec">
                    Five questions, drawn from what this level covered. It is
                    <strong class="text-ink">not an exam</strong> — nothing you already hold depends
                    on it, and there is no pass mark. Answer from memory rather than looking things
                    up; a score that reflects revision tells us nothing we can act on.
                </p>

                <p class="mt-3 text-xs text-ink-dis">Open until {{ refresher.closes_at }}.</p>
            </div>

            <!-- ─── QUESTIONS ───────────────────────────────── -->
            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <QuizQuestion
                    v-for="(question, index) in questions"
                    :key="question.id"
                    :question="question"
                    :index="index"
                    v-model="answers[question.id]"
                />

                <div class="card flex flex-wrap items-center gap-4 p-5">
                    <span class="text-sm text-ink-sec">
                        {{ answeredCount }} of {{ questions.length }} answered
                    </span>

                    <div class="flex-1"></div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="cursor-pointer rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover disabled:opacity-60"
                    >
                        {{ form.processing ? 'Submitting…' : 'Submit refresher' }}
                    </button>
                </div>

                <!-- Submitting with blanks is allowed and is itself a finding:
                     a question nobody can answer from memory is what the
                     refresher exists to surface. -->
                <p v-if="answeredCount < questions.length" class="text-center text-xs text-ink-dis">
                    You can submit with questions unanswered. Leaving one blank is more useful than
                    guessing.
                </p>
            </form>
        </article>
    </EmployeeLayout>
</template>

<script setup>
/**
 * A practical task, from the trainee's side.
 *
 * The rubric is shown before they start, not only after they are marked against
 * it. Assessing somebody against criteria they never saw is how you get the
 * "I didn't know that counted" conversation.
 */
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    course: { type: Object, required: true },
    task: { type: Object, required: true },
    rubric: { type: Array, required: true },
    passRule: { type: Object, required: true },
    submission: { type: Object, default: null },
    can: { type: Object, required: true },
});

const rubricOpen = ref(false);
const evidenceInput = ref(null);

const write = useForm({ body: props.submission?.body ?? '' });

const isEditable = computed(() => props.submission?.is_editable ?? false);

const statusTone = computed(() => {
    if (!props.submission) return 'neutral';

    if (props.submission.passed === true) return 'positive';
    if (props.submission.passed === false) return 'negative';
    if (props.submission.status === 'returned') return 'warning';
    if (props.submission.status === 'submitted') return 'primary';

    return 'neutral';
});

function start() {
    router.post(route('practical.start', [props.course.slug, props.task.slug]), {}, { preserveScroll: true });
}

function saveDraft() {
    write.put(route('practical.draft', [props.course.slug, props.task.slug]), { preserveScroll: true });
}

function handIn() {
    write.post(route('practical.submit', [props.course.slug, props.task.slug]), { preserveScroll: true });
}

function attach(event) {
    const file = event.target.files?.[0];

    if (!file) return;

    router.post(
        route('practical.attach', [props.course.slug, props.task.slug]),
        { file },
        {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                if (evidenceInput.value) evidenceInput.value.value = '';
            },
        },
    );
}

function detach(fileId) {
    router.delete(route('practical.detach', [props.course.slug, props.task.slug, fileId]), {
        preserveScroll: true,
    });
}

const field =
    'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

const button =
    'cursor-pointer rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover disabled:opacity-60';

const buttonQuiet =
    'cursor-pointer rounded-lg border border-line bg-surface px-5 py-2.5 text-sm font-semibold text-ink-sec transition-colors hover:bg-surface-alt disabled:opacity-60';
</script>

<template>
    <Head :title="task.title" />

    <EmployeeLayout>
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <Link
                :href="route('courses.show', course.slug)"
                class="text-sm font-medium text-ink-sec no-underline hover:text-brand"
            >
                ← {{ course.title }}
            </Link>
        </div>

        <div class="mx-auto max-w-3xl">
            <!-- ═══ BRIEF ═══════════════════════════════════ -->
            <div class="card mb-6 p-6 sm:p-8">
                <p v-if="task.lesson_title" class="mb-2 text-sm font-medium text-brand">
                    {{ task.lesson_title }}
                </p>

                <h1 class="mb-3 text-2xl font-extrabold text-navy">{{ task.title }}</h1>

                <div class="mb-6 flex flex-wrap items-center gap-2">
                    <StatusPill label="Practical task" tone="accent" />
                    <StatusPill
                        v-if="submission"
                        :label="submission.status_label"
                        :tone="statusTone"
                    />
                    <span v-if="task.estimated_minutes" class="chip bg-surface-alt text-ink-sec">
                        ~{{ task.estimated_minutes }} min
                    </span>
                </div>

                <!-- Safe: sanitised server-side through the lesson allowlist. -->
                <div class="lesson-prose prose max-w-none" v-html="task.brief"></div>

                <div v-if="task.submission_instructions" class="mt-6 rounded-lg bg-surface-alt p-4">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                        How to submit
                    </p>
                    <p class="text-sm whitespace-pre-wrap text-ink-sec">
                        {{ task.submission_instructions }}
                    </p>
                </div>

                <div v-if="task.expected_evidence" class="mt-3 rounded-lg bg-surface-alt p-4">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                        Evidence required
                    </p>
                    <p class="text-sm whitespace-pre-wrap text-ink-sec">
                        {{ task.expected_evidence }}
                    </p>
                </div>
            </div>

            <!-- ═══ RUBRIC ══════════════════════════════════ -->
            <div class="card mb-6 p-6">
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center justify-between text-left"
                    @click="rubricOpen = !rubricOpen"
                >
                    <span>
                        <span class="block font-semibold text-navy">How this is marked</span>
                        <span class="text-sm text-ink-sec">
                            Four criteria, 0–4 each. You need
                            {{ passRule.total_needed }}/{{ passRule.max_total }} overall, and at
                            least the minimum on every criterion.
                        </span>
                    </span>
                    <span class="ml-4 text-ink-dis">{{ rubricOpen ? '−' : '+' }}</span>
                </button>

                <div v-show="rubricOpen" class="mt-5 space-y-5">
                    <div v-for="criterion in rubric" :key="criterion.key">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-ink">{{ criterion.label }}</span>
                            <StatusPill
                                :label="`needs ${criterion.minimum}+`"
                                :tone="criterion.is_critical ? 'warning' : 'neutral'"
                            />
                        </div>

                        <dl class="grid gap-1 text-sm sm:grid-cols-2">
                            <div
                                v-for="(text, score) in criterion.descriptors"
                                :key="score"
                                class="flex gap-2 rounded px-2 py-1"
                                :class="
                                    Number(score) >= criterion.minimum
                                        ? 'bg-positive-bg/40'
                                        : 'bg-surface-alt'
                                "
                            >
                                <dt class="font-semibold text-ink-sec">{{ score }}</dt>
                                <dd class="text-ink-sec">{{ text }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- ═══ RETURNED ════════════════════════════════ -->
            <div
                v-if="submission?.status === 'returned'"
                class="card mb-6 border-l-4 border-l-warning p-6"
            >
                <p class="mb-1 font-semibold text-navy">Sent back for another go</p>
                <p class="text-sm whitespace-pre-wrap text-ink-sec">
                    {{ submission.returned_reason }}
                </p>
                <p class="mt-2 text-xs text-ink-dis">
                    This is still attempt {{ submission.attempt_number }} — it does not count as a
                    fail.
                </p>
            </div>

            <!-- ═══ NOT STARTED ═════════════════════════════ -->
            <div v-if="!submission" class="card mb-6 p-6 text-center">
                <p v-if="can.attempt" class="mb-4 text-sm text-ink-sec">
                    Do the task, then write up what you did and attach your evidence.
                </p>
                <p v-else class="text-sm text-ink-sec">
                    This task is not assigned to you — you can read it, but not submit.
                </p>

                <button v-if="can.attempt" type="button" :class="button" @click="start">
                    Start this task
                </button>
            </div>

            <!-- ═══ WRITE-UP ════════════════════════════════ -->
            <div v-else-if="isEditable" class="card mb-6 p-6">
                <label class="mb-1.5 block text-sm font-medium text-navy" for="body">
                    Your write-up
                </label>
                <p class="mb-3 text-sm text-ink-sec">
                    Say what you did, in what order, and how you verified it. Method, Verification
                    and Communication are all scored from this.
                </p>

                <textarea
                    id="body"
                    v-model="write.body"
                    rows="10"
                    :class="field"
                    placeholder="I started by checking the raw sensor value, which was arriving normally…"
                ></textarea>

                <p v-if="write.errors.body" class="mt-1.5 text-sm text-negative">
                    {{ write.errors.body }}
                </p>

                <!-- ─── EVIDENCE ─── -->
                <div class="mt-6 border-t border-line pt-5">
                    <p class="mb-2 text-sm font-medium text-navy">Evidence</p>

                    <ul v-if="submission.files.length" class="mb-3 space-y-2">
                        <li
                            v-for="file in submission.files"
                            :key="file.id"
                            class="flex items-center justify-between rounded-lg bg-surface-alt px-3 py-2 text-sm"
                        >
                            <a
                                :href="file.download_url"
                                class="truncate font-medium text-brand no-underline hover:underline"
                            >
                                {{ file.name }}
                            </a>
                            <span class="ml-3 flex shrink-0 items-center gap-3">
                                <span class="text-xs text-ink-dis">{{ file.size }}</span>
                                <button
                                    type="button"
                                    class="cursor-pointer text-xs font-semibold text-negative"
                                    @click="detach(file.id)"
                                >
                                    Remove
                                </button>
                            </span>
                        </li>
                    </ul>

                    <input
                        ref="evidenceInput"
                        type="file"
                        class="block w-full text-sm text-ink-sec file:mr-3 file:cursor-pointer file:rounded-lg file:border-0 file:bg-surface-alt file:px-4 file:py-2 file:text-sm file:font-semibold file:text-ink-sec"
                        @change="attach"
                    />
                    <p class="mt-1.5 text-xs text-ink-dis">
                        Screenshots, PDFs or plain logs, up to 20 MB each.
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <button
                        type="button"
                        :class="buttonQuiet"
                        :disabled="write.processing"
                        @click="saveDraft"
                    >
                        Save draft
                    </button>
                    <button
                        type="button"
                        :class="button"
                        :disabled="write.processing"
                        @click="handIn"
                    >
                        Hand in for marking
                    </button>
                </div>
            </div>

            <!-- ═══ AWAITING MARKING ════════════════════════ -->
            <div v-else-if="submission.status === 'submitted'" class="card mb-6 p-6">
                <p class="mb-2 font-semibold text-navy">Handed in</p>
                <p class="text-sm text-ink-sec">
                    Submitted {{ submission.submitted_at }}. Your trainer will mark it against the
                    rubric above.
                </p>

                <div class="mt-4 rounded-lg bg-surface-alt p-4">
                    <p class="mb-1 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                        What you submitted
                    </p>
                    <p class="text-sm whitespace-pre-wrap text-ink-sec">{{ submission.body }}</p>
                </div>
            </div>

            <!-- ═══ MARKED ══════════════════════════════════ -->
            <div v-else class="card mb-6 p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="font-semibold text-navy">Your marks</p>
                    <span class="text-lg font-extrabold text-navy">
                        {{ submission.total_score }}/{{ passRule.max_total }}
                    </span>
                </div>

                <div
                    v-for="(grading, index) in submission.feedback"
                    :key="index"
                    class="mb-5 last:mb-0"
                >
                    <p v-if="submission.feedback.length > 1" class="mb-2 text-xs text-ink-dis">
                        Marked by {{ grading.grader }}
                    </p>

                    <p class="mb-3 text-sm text-ink-sec">{{ grading.explanation }}</p>

                    <div
                        v-for="score in grading.scores"
                        :key="score.criterion"
                        class="mb-2 rounded-lg p-3"
                        :class="score.meets_standard ? 'bg-positive-bg/40' : 'bg-negative-bg/40'"
                    >
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="font-semibold text-ink">{{ score.criterion }}</span>
                            <span class="text-sm font-bold text-ink">{{ score.score }}/4</span>
                        </div>
                        <p class="text-sm text-ink-sec">{{ score.descriptor }}</p>
                        <p v-if="score.comment" class="mt-1.5 text-sm text-ink">
                            {{ score.comment }}
                        </p>
                    </div>

                    <p v-if="grading.summary" class="mt-3 text-sm whitespace-pre-wrap text-ink-sec">
                        {{ grading.summary }}
                    </p>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>

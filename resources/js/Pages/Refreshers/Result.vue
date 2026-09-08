<script setup>
/**
 * What the refresher said.
 *
 * Every question is shown with its explanation, right or wrong. The whole point
 * of a refresher is to find what has faded, so the wrong answers are the
 * valuable part — showing only a score would throw away everything it was for.
 */
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    refresher: { type: Object, required: true },
    answers: { type: Array, default: () => [] },
});

const correctCount = computed(() => props.answers.filter((a) => a.is_correct).length);

// Above 75% of the original is the target in §7 of the plan (KPI 6).
const retentionTone = computed(() => {
    if (props.refresher.retention === null) return 'neutral';
    return props.refresher.retention > 75 ? 'positive' : 'warning';
});
</script>

<template>
    <Head :title="refresher.label" />

    <EmployeeLayout>
        <article class="mx-auto max-w-3xl">
            <!-- ─── HEADLINE ────────────────────────────────── -->
            <div class="card mb-6 p-6 sm:p-8">
                <p class="mb-1 text-sm font-medium text-brand">
                    {{ refresher.level }}<span v-if="refresher.area"> · {{ refresher.area }}</span>
                </p>

                <h1 class="mb-4 text-2xl font-extrabold text-navy">{{ refresher.label }}</h1>

                <div class="flex flex-wrap items-end gap-8">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-ink-dis uppercase">
                            Score
                        </p>
                        <p class="text-3xl font-extrabold text-navy">
                            {{ correctCount }} / {{ answers.length }}
                        </p>
                    </div>

                    <div v-if="refresher.retention !== null">
                        <p class="text-xs font-semibold tracking-wide text-ink-dis uppercase">
                            Against your original
                        </p>
                        <p class="text-3xl font-extrabold text-navy">
                            {{ refresher.retention }}%
                        </p>
                    </div>

                    <div class="flex-1"></div>

                    <StatusPill
                        v-if="refresher.retention !== null"
                        :label="refresher.retention > 75 ? 'Holding' : 'Fading'"
                        :tone="retentionTone"
                    />
                </div>

                <p class="mt-5 border-t border-line pt-4 text-sm leading-relaxed text-ink-sec">
                    <span v-if="refresher.retention === null">
                        Your level was granted directly rather than by an exam, so there is no
                        original score to compare this against. The answers below still stand on
                        their own.
                    </span>
                    <span v-else-if="refresher.retention > 75">
                        Most of what you learned is still there. Read the explanations for anything
                        below and carry on.
                    </span>
                    <span v-else>
                        Some of this has faded — which is normal, expected, and exactly what this
                        was for. <strong class="text-ink">Nothing you hold is affected.</strong>
                        Read the explanations below; they are the useful part.
                    </span>
                </p>
            </div>

            <!-- ─── EVERY QUESTION ──────────────────────────── -->
            <div class="flex flex-col gap-4">
                <div v-for="answer in answers" :key="answer.position" class="card p-6">
                    <div class="mb-3 flex items-start gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="
                                answer.is_correct
                                    ? 'bg-positive-bg text-ok'
                                    : 'bg-warning-bg text-warning-dim dark:text-warning'
                            "
                        >
                            {{ answer.is_correct ? '✓' : '·' }}
                        </span>

                        <p class="flex-1 text-sm font-semibold text-ink">{{ answer.prompt }}</p>
                    </div>

                    <ul class="mb-3 flex flex-col gap-1.5 pl-9">
                        <li
                            v-for="(option, i) in answer.options"
                            :key="i"
                            class="flex items-center gap-2 text-sm"
                            :class="
                                option.is_correct
                                    ? 'font-medium text-ok'
                                    : option.chosen
                                      ? 'text-ink-sec line-through'
                                      : 'text-ink-dis'
                            "
                        >
                            <span class="w-4 shrink-0 text-xs">
                                {{ option.is_correct ? '✓' : option.chosen ? '✕' : '' }}
                            </span>
                            <span>{{ option.label }}</span>
                            <span v-if="option.chosen" class="text-xs text-ink-dis">(you)</span>
                        </li>
                    </ul>

                    <p
                        v-if="answer.explanation"
                        class="ml-9 rounded-lg bg-surface-alt px-4 py-3 text-sm leading-relaxed text-ink-sec"
                    >
                        {{ answer.explanation }}
                    </p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <Link
                    :href="route('dashboard')"
                    class="inline-block rounded-lg border border-line px-5 py-2.5 text-sm font-medium text-ink-sec no-underline transition-colors hover:border-brand hover:text-brand"
                >
                    Back to dashboard
                </Link>
            </div>
        </article>
    </EmployeeLayout>
</template>

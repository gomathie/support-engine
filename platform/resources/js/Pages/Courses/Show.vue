<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import ProgressGauge from '@/Components/ProgressGauge.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatusPill from '@/Components/StatusPill.vue';

defineProps({
    course: { type: Object, required: true },
    modules: { type: Array, default: () => [] },
    progress: { type: Object, required: true },
    final_quiz: { type: Object, default: null },
    can: { type: Object, default: () => ({}) },
});

const collapsed = ref({});
const toggle = (id) => (collapsed.value[id] = !collapsed.value[id]);
</script>

<template>
    <Head :title="course.title" />

    <EmployeeLayout>
        <div class="mx-auto max-w-[880px] px-5 py-8">
            <!-- ─── HEADER ──────────────────────────────────── -->
            <div class="mb-8 flex flex-wrap items-center gap-6">
                <ProgressGauge :percentage="progress.percentage" />

                <div class="min-w-[220px] flex-1">
                    <p class="mono-label mb-1.5 text-[11px] tracking-[3px] text-primary">
                        {{ course.category || 'Course' }}
                    </p>
                    <h1 class="mb-1.5 text-xl leading-tight font-bold">{{ course.title }}</h1>
                    <p v-if="course.summary" class="mb-2 text-[13px] text-ink-sec">
                        {{ course.summary }}
                    </p>

                    <div class="flex flex-wrap items-center gap-2">
                        <StatusPill :label="progress.status_label" :tone="progress.status_tone" />
                        <StatusPill v-if="course.is_required" label="Required" tone="negative" />
                        <span class="mono-label text-[10px] tracking-[1px] text-ink-dis">
                            {{ progress.completed_lessons }}/{{ progress.total_lessons }} lessons
                        </span>
                        <span
                            v-if="course.estimated_minutes"
                            class="mono-label text-[10px] tracking-[1px] text-ink-dis"
                        >
                            ~{{ course.estimated_minutes }} min
                        </span>
                        <span
                            v-if="course.instructor"
                            class="mono-label text-[10px] tracking-[1px] text-ink-dis"
                        >
                            {{ course.instructor }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="course.description"
                class="mb-8 rounded-lg border border-line bg-surface px-4 py-3.5 text-[13px] leading-relaxed text-ink-sec"
            >
                {{ course.description }}
            </div>

            <!-- ─── MODULES ─────────────────────────────────── -->
            <div v-for="module in modules" :key="module.id" class="mb-5">
                <button
                    type="button"
                    class="mb-2.5 flex w-full cursor-pointer items-center gap-2.5 rounded-md border border-line bg-surface-alt px-3.5 py-2.5 text-left select-none"
                    :aria-expanded="!collapsed[module.id]"
                    @click="toggle(module.id)"
                >
                    <span
                        class="rounded-[3px] bg-warning px-2 py-0.5 font-mono text-[11px] font-bold tracking-[1px] text-on-warning"
                    >
                        {{ module.title }}
                    </span>
                    <h2 class="flex-1 text-[15px] font-bold">
                        {{ module.subtitle || module.title }}
                    </h2>
                    <span class="font-mono text-xs text-ink-sec">
                        {{ module.completed_count }}/{{ module.lesson_count }}
                    </span>
                    <span
                        class="text-xs text-ink-dis transition-transform duration-150"
                        :class="collapsed[module.id] ? '-rotate-90' : ''"
                    >
                        ▾
                    </span>
                </button>

                <div v-show="!collapsed[module.id]">
                    <p
                        v-if="module.description"
                        class="mb-2.5 px-1 text-xs leading-relaxed text-ink-sec"
                    >
                        {{ module.description }}
                    </p>

                    <div class="overflow-hidden rounded-lg border border-line bg-surface">
                        <Link
                            v-for="lesson in module.lessons"
                            :key="lesson.id"
                            :href="route('lessons.show', [course.slug, lesson.slug])"
                            class="flex items-center gap-3 border-b border-line px-4 py-3 no-underline last:border-b-0 hover:bg-surface-alt"
                        >
                            <span
                                class="relative h-4 w-4 shrink-0 rounded-[3px] border-[1.5px]"
                                :class="
                                    lesson.completed
                                        ? 'border-primary bg-primary'
                                        : 'border-ink-dis'
                                "
                            >
                                <span
                                    v-if="lesson.completed"
                                    class="absolute top-0 left-[4px] h-2 w-1 rotate-45 border-r-2 border-b-2 border-surface"
                                ></span>
                            </span>

                            <span
                                class="flex-1 text-[13px]"
                                :class="lesson.completed ? 'text-ink-dis line-through' : 'text-ink'"
                            >
                                {{ lesson.title }}
                            </span>

                            <span
                                v-if="lesson.has_quiz"
                                class="mono-label text-[9px] tracking-[1px] text-warning"
                            >
                                Quiz
                            </span>
                            <span class="mono-label text-[9px] tracking-[1px] text-ink-dis">
                                {{ lesson.type_label }}
                            </span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ─── FINAL ASSESSMENT ────────────────────────── -->
            <div
                v-if="final_quiz"
                class="mt-8 rounded-lg border px-5 py-4"
                :class="
                    final_quiz.passed
                        ? 'border-positive bg-positive-bg'
                        : final_quiz.unlocked
                          ? 'border-warning-dim bg-warning-bg'
                          : 'border-line bg-surface'
                "
            >
                <div class="mb-1.5 flex items-center gap-2.5">
                    <span class="mono-label text-[10px] tracking-[2px] text-ink-sec">
                        Final assessment
                    </span>
                    <StatusPill
                        v-if="final_quiz.passed"
                        label="Passed"
                        tone="positive"
                    />
                </div>

                <h2 class="mb-1 text-base font-bold">{{ final_quiz.title }}</h2>
                <p v-if="final_quiz.description" class="mb-3 text-xs text-ink-sec">
                    {{ final_quiz.description }}
                </p>

                <div class="mono-label mb-3 flex flex-wrap gap-4 text-[10px] tracking-[1px] text-ink-sec">
                    <span>Pass mark {{ final_quiz.passing_score }}%</span>
                    <span v-if="final_quiz.max_attempts">
                        {{ final_quiz.attempts_used }}/{{ final_quiz.max_attempts }} attempts used
                    </span>
                    <span v-else>{{ final_quiz.attempts_used }} attempts taken</span>
                    <span v-if="final_quiz.best_score > 0">
                        Best {{ Math.round(final_quiz.best_score) }}%
                    </span>
                </div>

                <Link
                    v-if="final_quiz.unlocked"
                    :href="route('quizzes.show', [course.slug, final_quiz.id])"
                    class="mono-label inline-block rounded-[5px] border border-primary bg-primary px-4 py-2 text-[11px] font-bold tracking-[1.5px] text-on-accent no-underline"
                >
                    {{ final_quiz.passed ? 'Review results' : 'Take the assessment' }}
                </Link>

                <!-- Locked in the UI *and* by QuizPolicy::attempt — the button
                     being hidden is the courtesy, the policy is the control. -->
                <p v-else class="text-xs text-ink-dis italic">
                    Complete every lesson to unlock the final assessment.
                </p>
            </div>
        </div>
    </EmployeeLayout>
</template>

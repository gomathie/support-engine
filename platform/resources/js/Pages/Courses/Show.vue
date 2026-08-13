<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import ProgressBar from '@/Components/ProgressBar.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
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
        <Link
            :href="route('courses.index')"
            class="mb-4 inline-block text-sm font-medium text-ink-sec no-underline hover:text-brand"
        >
            ← All courses
        </Link>

        <!-- ─── HEADER ──────────────────────────────────────── -->
        <section class="brand-gradient mb-8 rounded-2xl p-7 text-white sm:p-9">
            <div class="mb-3 flex flex-wrap items-center gap-2">
                <span v-if="course.category" class="chip bg-white/15 text-white">
                    {{ course.category }}
                </span>
                <span v-if="course.is_required" class="chip bg-white/15 text-white">Required</span>
                <span v-if="course.difficulty" class="chip bg-white/15 text-white">
                    {{ course.difficulty }}
                </span>
            </div>

            <h1 class="mb-2 text-2xl font-extrabold text-white sm:text-3xl">{{ course.title }}</h1>

            <p v-if="course.summary" class="mb-5 max-w-2xl text-sm text-white/80 sm:text-base">
                {{ course.summary }}
            </p>

            <div class="max-w-md">
                <div class="h-2 overflow-hidden rounded-full bg-white/20">
                    <div
                        class="h-full rounded-full bg-white transition-[width] duration-500"
                        :style="{ width: `${progress.percentage}%` }"
                    ></div>
                </div>
                <p class="mt-2 text-sm text-white/80">
                    {{ progress.completed_lessons }} of {{ progress.total_lessons }} lessons ·
                    {{ Math.round(progress.percentage) }}%
                </p>
            </div>
        </section>

        <div
            v-if="course.description"
            class="card mb-8 p-6 text-sm leading-relaxed text-ink-sec"
        >
            {{ course.description }}
        </div>

        <!-- ─── MODULES ─────────────────────────────────────── -->
        <div class="mb-8 flex flex-col gap-3">
            <div v-for="module in modules" :key="module.id" class="card overflow-hidden">
                <button
                    type="button"
                    class="flex w-full cursor-pointer items-center gap-3 px-5 py-4 text-left transition-colors hover:bg-surface-alt"
                    :aria-expanded="!collapsed[module.id]"
                    @click="toggle(module.id)"
                >
                    <span
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                        :class="
                            module.lesson_count > 0 && module.completed_count === module.lesson_count
                                ? 'bg-ok text-white'
                                : 'bg-surface-alt text-ink-sec'
                        "
                    >
                        <template
                            v-if="
                                module.lesson_count > 0 &&
                                module.completed_count === module.lesson_count
                            "
                        >
                            ✓
                        </template>
                        <template v-else>{{ module.completed_count }}</template>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-bold text-navy">{{ module.title }}</span>
                        <span v-if="module.subtitle" class="block truncate text-sm text-ink-sec">
                            {{ module.subtitle }}
                        </span>
                    </span>

                    <span class="shrink-0 text-xs font-medium text-ink-dis">
                        {{ module.completed_count }}/{{ module.lesson_count }}
                    </span>

                    <svg
                        class="h-4 w-4 shrink-0 fill-none stroke-current stroke-2 text-ink-dis transition-transform"
                        :class="collapsed[module.id] ? '-rotate-90' : ''"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                    </svg>
                </button>

                <div v-show="!collapsed[module.id]" class="divide-y divide-line border-t border-line">
                    <p
                        v-if="module.description"
                        class="px-5 py-3 text-sm leading-relaxed text-ink-sec"
                    >
                        {{ module.description }}
                    </p>

                    <Link
                        v-for="lesson in module.lessons"
                        :key="lesson.id"
                        :href="route('lessons.show', [course.slug, lesson.slug])"
                        class="flex items-center gap-3 px-5 py-3 no-underline transition-colors hover:bg-surface-alt"
                    >
                        <span
                            class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-md border-2"
                            :class="
                                lesson.completed ? 'border-ok bg-ok' : 'border-line-strong'
                            "
                        >
                            <svg
                                v-if="lesson.completed"
                                class="h-3 w-3 fill-none stroke-white stroke-[3]"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>

                        <span
                            class="flex-1 text-sm"
                            :class="lesson.completed ? 'text-ink-dis line-through' : 'text-ink'"
                        >
                            {{ lesson.title }}
                        </span>

                        <span v-if="lesson.has_quiz" class="chip bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
                            Quiz
                        </span>
                        <span class="chip hidden bg-surface-alt text-ink-sec sm:inline-flex">
                            {{ lesson.type_label }}
                        </span>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ─── FINAL ASSESSMENT ────────────────────────────── -->
        <div
            v-if="final_quiz"
            class="card p-6"
            :class="final_quiz.passed ? 'border-ok/40' : ''"
        >
            <div class="mb-2 flex flex-wrap items-center gap-2">
                <span class="chip bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
                    Final assessment
                </span>
                <StatusPill v-if="final_quiz.passed" label="Passed" tone="positive" />
            </div>

            <h2 class="mb-1 text-lg font-bold text-navy">{{ final_quiz.title }}</h2>

            <p v-if="final_quiz.description" class="mb-4 text-sm text-ink-sec">
                {{ final_quiz.description }}
            </p>

            <div class="mb-5 flex flex-wrap gap-x-6 gap-y-1 text-sm text-ink-sec">
                <span>Pass mark <strong class="text-navy">{{ final_quiz.passing_score }}%</strong></span>
                <span v-if="final_quiz.max_attempts">
                    {{ final_quiz.attempts_used }} of {{ final_quiz.max_attempts }} attempts used
                </span>
                <span v-else>{{ final_quiz.attempts_used }} attempts taken</span>
                <span v-if="final_quiz.best_score > 0">
                    Best <strong class="text-navy">{{ Math.round(final_quiz.best_score) }}%</strong>
                </span>
            </div>

            <Link
                v-if="final_quiz.unlocked"
                :href="route('quizzes.show', [course.slug, final_quiz.id])"
                class="inline-block rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white no-underline transition-colors hover:bg-brand-hover"
            >
                {{ final_quiz.passed ? 'Review results' : 'Take the assessment' }}
            </Link>

            <!-- Locked in the UI *and* by QuizPolicy::attempt — the hidden
                 button is the courtesy, the policy is the control. -->
            <p v-else class="text-sm text-ink-dis italic">
                Complete every lesson to unlock the final assessment.
            </p>
        </div>
    </EmployeeLayout>
</template>

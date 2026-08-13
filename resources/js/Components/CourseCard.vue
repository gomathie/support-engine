<script setup>
/**
 * The academy's course card: white, rounded-2xl, hairline border, a gradient
 * thumbnail band with the course initial, chips for level and content, a
 * progress bar, and a half-step lift on hover.
 */
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProgressBar from '@/Components/ProgressBar.vue';

const props = defineProps({
    course: { type: Object, required: true },
    showProgress: { type: Boolean, default: true },
});

const initial = computed(() => (props.course.title ?? '?').trim().charAt(0).toUpperCase());

const isComplete = computed(() => props.course.status === 'completed');

const difficultyLabel = computed(() =>
    props.course.difficulty
        ? props.course.difficulty.charAt(0).toUpperCase() + props.course.difficulty.slice(1)
        : null,
);

const lessonsDone = computed(() => props.course.completed_lessons ?? 0);
const lessonsTotal = computed(
    () => props.course.total_lessons ?? props.course.lesson_count ?? 0,
);
</script>

<template>
    <Link
        :href="route('courses.show', course.slug)"
        class="card card-interactive group flex flex-col overflow-hidden no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
    >
        <!-- Thumbnail band -->
        <div class="brand-gradient relative flex h-28 items-center justify-center">
            <span class="text-4xl font-extrabold text-white/90">{{ initial }}</span>

            <span
                v-if="isComplete"
                class="absolute top-3 right-3 flex h-7 w-7 items-center justify-center rounded-full bg-ok text-sm text-white shadow"
                aria-label="Completed"
            >
                ✓
            </span>

            <span
                v-if="course.is_required"
                class="chip absolute top-3 left-3 bg-white/15 text-white"
            >
                Required
            </span>
        </div>

        <div class="flex flex-1 flex-col p-5">
            <div class="mb-2 flex flex-wrap items-center gap-1.5">
                <span v-if="course.category" class="chip bg-brand-soft text-brand">
                    {{ course.category }}
                </span>
                <span v-if="difficultyLabel" class="chip bg-surface-alt text-ink-sec">
                    {{ difficultyLabel }}
                </span>
            </div>

            <h3 class="mb-1.5 text-base leading-snug font-bold text-navy">
                {{ course.title }}
            </h3>

            <p class="mb-4 line-clamp-2 flex-1 text-sm leading-relaxed text-ink-sec">
                {{ course.summary }}
            </p>

            <div v-if="showProgress" class="mb-3">
                <ProgressBar
                    :percentage="course.percentage"
                    :tone="isComplete ? 'positive' : 'primary'"
                />
                <div class="mt-1.5 flex justify-between text-xs text-ink-sec">
                    <span>{{ lessonsDone }} of {{ lessonsTotal }} lessons</span>
                    <span class="font-semibold">{{ Math.round(course.percentage) }}%</span>
                </div>
            </div>

            <div class="flex items-center justify-between text-sm">
                <span class="font-semibold text-brand">
                    {{
                        isComplete
                            ? 'Review'
                            : course.percentage > 0
                              ? 'Continue'
                              : 'Start course'
                    }}
                </span>
                <span
                    class="text-brand transition-transform duration-200 group-hover:translate-x-1"
                >
                    →
                </span>
            </div>
        </div>
    </Link>
</template>

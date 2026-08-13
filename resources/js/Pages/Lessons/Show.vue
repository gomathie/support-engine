<script setup>
/**
 * The lesson viewer: a table of contents on the left, the lesson "paper" on
 * the right, and a branch per content type.
 *
 * Adding a content type means adding a branch here and a case to the LessonType
 * enum. Nothing else changes.
 */
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import LessonToc from '@/Components/LessonToc.vue';
import AnnotationDrawer from '@/Components/AnnotationDrawer.vue';
import StatusPill from '@/Components/StatusPill.vue';

const props = defineProps({
    course: { type: Object, required: true },
    lesson: { type: Object, required: true },
    resources: { type: Array, default: () => [] },
    annotations: { type: Array, default: () => [] },
    quiz: { type: Object, default: null },
    navigation: { type: Object, required: true },
    state: { type: Object, required: true },
});

const drawerOpen = ref(false);
const tocOpen = ref(false);
const saving = ref(false);

const unresolvedCount = computed(() => props.annotations.filter((a) => !a.is_resolved).length);
const primaryResource = computed(() => props.resources[0] ?? null);

function toggleComplete() {
    saving.value = true;

    router.visit(
        route(props.state.completed ? 'lessons.uncomplete' : 'lessons.complete', [
            props.course.slug,
            props.lesson.slug,
        ]),
        {
            method: props.state.completed ? 'delete' : 'post',
            preserveScroll: true,
            onFinish: () => (saving.value = false),
        },
    );
}
</script>

<template>
    <Head :title="lesson.title" />

    <EmployeeLayout>
        <!-- ─── BREADCRUMB ──────────────────────────────────── -->
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <Link
                :href="route('courses.show', course.slug)"
                class="text-sm font-medium text-ink-sec no-underline hover:text-brand"
            >
                ← {{ course.title }}
            </Link>

            <div class="flex-1"></div>

            <span v-if="navigation.position" class="text-sm text-ink-dis">
                Lesson {{ navigation.position }} of {{ navigation.total }}
            </span>

            <button
                v-if="unresolvedCount"
                type="button"
                class="chip cursor-pointer bg-warning-bg text-warning-dim dark:text-warning"
                @click="drawerOpen = true"
            >
                {{ unresolvedCount }} standard defaults
            </button>

            <button
                type="button"
                class="rounded-lg border border-line px-3 py-1.5 text-sm font-medium text-ink-sec lg:hidden"
                @click="tocOpen = !tocOpen"
            >
                Contents
            </button>
        </div>

        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[220px_1fr]">
            <!-- ─── TOC ─────────────────────────────────────── -->
            <div :class="tocOpen ? 'block' : 'hidden lg:block'">
                <div class="card p-4 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none">
                    <LessonToc content-selector="#lesson-body" />
                </div>
            </div>

            <!-- ─── ARTICLE ─────────────────────────────────── -->
            <article class="card min-w-0 p-6 sm:p-9">
                <p v-if="lesson.module_title" class="mb-2 text-sm font-medium text-brand">
                    {{ lesson.module_title }}
                </p>

                <h1 class="mb-3 text-2xl leading-tight font-extrabold text-navy sm:text-3xl">
                    {{ lesson.title }}
                </h1>

                <p v-if="lesson.description" class="mb-4 text-base text-ink-sec">
                    {{ lesson.description }}
                </p>

                <div class="mb-7 flex flex-wrap items-center gap-2 border-b border-line pb-5">
                    <StatusPill
                        :label="state.completed ? 'Completed' : 'Not completed'"
                        :tone="state.completed ? 'positive' : 'neutral'"
                    />
                    <span class="chip bg-surface-alt text-ink-sec">{{ lesson.type_label }}</span>
                    <span v-if="lesson.estimated_minutes" class="chip bg-surface-alt text-ink-sec">
                        ~{{ lesson.estimated_minutes }} min
                    </span>
                </div>

                <!-- ═══ CONTENT BY TYPE ═════════════════════════ -->

                <!-- v-html is safe here: the server ran the body through
                     HTMLPurifier's `lesson` allowlist, which strips script,
                     iframe, style and every event handler. -->
                <div
                    v-if="lesson.type === 'rich_text' && lesson.content"
                    id="lesson-body"
                    class="lesson-prose prose max-w-none"
                    v-html="lesson.content"
                ></div>

                <div v-else-if="lesson.type === 'pdf' && primaryResource" id="lesson-body">
                    <iframe
                        :src="primaryResource.stream_url"
                        class="h-[70vh] w-full rounded-xl border border-line"
                        :title="primaryResource.name"
                    ></iframe>
                </div>

                <div v-else-if="lesson.type === 'image' && primaryResource" id="lesson-body">
                    <img
                        :src="primaryResource.stream_url"
                        :alt="primaryResource.description || lesson.title"
                        class="max-w-full rounded-xl border border-line"
                    />
                </div>

                <div
                    v-else-if="lesson.type === 'external_link' && lesson.external_url"
                    id="lesson-body"
                >
                    <a
                        :href="lesson.external_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-block rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-brand-hover"
                    >
                        Open external resource ↗
                    </a>
                    <p class="mt-2 text-sm break-all text-ink-dis">{{ lesson.external_url }}</p>
                </div>

                <div v-else id="lesson-body">
                    <div
                        v-if="lesson.content"
                        class="lesson-prose prose max-w-none"
                        v-html="lesson.content"
                    ></div>
                    <p v-else class="text-sm text-ink-dis italic">
                        This lesson has no inline content — see the resources below.
                    </p>
                </div>

                <!-- ═══ RESOURCES ═══════════════════════════════ -->
                <div v-if="resources.length" class="mt-9 border-t border-line pt-6">
                    <h2 class="mb-3 text-base font-bold text-navy">Resources</h2>

                    <ul class="flex flex-col gap-2">
                        <li
                            v-for="resource in resources"
                            :key="resource.id"
                            class="flex items-center gap-3 rounded-xl border border-line px-4 py-3"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-ink">
                                    {{ resource.name }}
                                </div>
                                <div v-if="resource.description" class="truncate text-xs text-ink-sec">
                                    {{ resource.description }}
                                </div>
                            </div>

                            <span class="shrink-0 text-xs text-ink-dis">{{ resource.size }}</span>

                            <a
                                v-if="resource.is_downloadable"
                                :href="resource.download_url"
                                class="shrink-0 rounded-lg border border-line px-3 py-1.5 text-sm font-medium text-ink-sec no-underline transition-colors hover:border-brand hover:text-brand"
                            >
                                Download
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- ═══ ATTACHED QUIZ ═══════════════════════════ -->
                <div
                    v-if="quiz"
                    class="mt-9 rounded-xl border p-5"
                    :class="quiz.passed ? 'border-ok/40 bg-positive-bg' : 'border-line bg-surface-alt'"
                >
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <span class="chip bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300">
                            Knowledge check
                        </span>
                        <StatusPill v-if="quiz.passed" label="Passed" tone="positive" />
                    </div>

                    <h3 class="mb-3 text-base font-bold text-navy">{{ quiz.title }}</h3>

                    <Link
                        :href="route('quizzes.show', [course.slug, quiz.id])"
                        class="inline-block rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white no-underline hover:bg-brand-hover"
                    >
                        {{ quiz.passed ? 'Review' : 'Take quiz' }}
                    </Link>
                </div>

                <!-- ═══ COMPLETE ════════════════════════════════ -->
                <div
                    v-if="state.can_complete && lesson.completion_requirement === 'acknowledge'"
                    class="mt-9 border-t border-line pt-6"
                >
                    <button
                        type="button"
                        :disabled="saving"
                        class="cursor-pointer rounded-lg px-5 py-2.5 text-sm font-semibold transition-colors disabled:opacity-60"
                        :class="
                            state.completed
                                ? 'border border-line bg-transparent text-ink-sec hover:border-ink-dis'
                                : 'bg-ok text-white hover:opacity-90'
                        "
                        @click="toggleComplete"
                    >
                        {{ state.completed ? 'Mark as not complete' : '✓ Mark complete' }}
                    </button>
                </div>

                <p
                    v-else-if="lesson.completion_requirement === 'quiz'"
                    class="mt-9 border-t border-line pt-6 text-sm text-ink-dis italic"
                >
                    This lesson is completed by passing its quiz.
                </p>
            </article>
        </div>

        <!-- ─── PREV / NEXT ─────────────────────────────────── -->
        <div class="mt-8 flex flex-wrap gap-3">
            <Link
                v-if="navigation.previous"
                :href="navigation.previous.url"
                class="card card-interactive flex-1 p-4 no-underline"
            >
                <span class="block text-xs font-semibold tracking-wide text-ink-dis uppercase">
                    Previous
                </span>
                <span class="mt-0.5 block text-sm font-medium text-ink">
                    {{ navigation.previous.title }}
                </span>
            </Link>

            <Link
                v-if="navigation.next"
                :href="navigation.next.url"
                class="card card-interactive flex-1 p-4 text-right no-underline"
            >
                <span class="block text-xs font-semibold tracking-wide text-ink-dis uppercase">
                    Next
                </span>
                <span class="mt-0.5 block text-sm font-medium text-ink">
                    {{ navigation.next.title }}
                </span>
            </Link>
        </div>

        <AnnotationDrawer
            :open="drawerOpen"
            :annotations="annotations"
            @close="drawerOpen = false"
        />
    </EmployeeLayout>
</template>

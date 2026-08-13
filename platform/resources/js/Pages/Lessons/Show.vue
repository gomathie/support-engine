<script setup>
/**
 * The lesson viewer. Carries the skills module's layout — sticky TOC on the
 * left, "paper" article on the right, annotation drawer — and branches on the
 * lesson's content type.
 *
 * Adding a content type means adding a branch here and a case to the
 * LessonType enum. Nothing else changes.
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

    const method = props.state.completed ? 'delete' : 'post';

    router.visit(route(props.state.completed ? 'lessons.uncomplete' : 'lessons.complete', [
        props.course.slug,
        props.lesson.slug,
    ]), {
        method,
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
}
</script>

<template>
    <Head :title="lesson.title" />

    <EmployeeLayout>
        <!-- ─── SUB-MASTHEAD ────────────────────────────────── -->
        <div class="sticky top-[54px] z-30 border-b border-line bg-nav backdrop-blur-[6px]">
            <div class="mx-auto flex max-w-[1220px] items-center gap-4 px-5.5 py-2.5">
                <Link
                    :href="route('courses.show', course.slug)"
                    class="mono-label text-[11px] tracking-[2.5px] whitespace-nowrap text-primary no-underline"
                >
                    {{ course.title }}
                </Link>

                <div class="flex-1"></div>

                <span
                    v-if="navigation.position"
                    class="mono-label hidden text-[10px] tracking-[1px] text-ink-dis sm:block"
                >
                    {{ navigation.position }} / {{ navigation.total }}
                </span>

                <button
                    v-if="unresolvedCount"
                    type="button"
                    class="mono-label cursor-pointer rounded border border-warning-dim px-2.5 py-1.5 text-[11px] tracking-[1px] whitespace-nowrap text-warning transition-colors hover:bg-warning hover:text-on-warning"
                    @click="drawerOpen = true"
                >
                    {{ unresolvedCount }} standard defaults
                </button>

                <button
                    type="button"
                    class="mono-label cursor-pointer rounded border border-line px-2.5 py-1.5 text-[11px] text-ink-dis lg:hidden"
                    @click="tocOpen = !tocOpen"
                >
                    Contents
                </button>
            </div>
        </div>

        <div
            class="mx-auto grid max-w-[1220px] grid-cols-1 items-start gap-7.5 px-5.5 pt-6.5 pb-17 lg:grid-cols-[225px_1fr]"
        >
            <!-- ─── TOC ─────────────────────────────────────── -->
            <div :class="tocOpen ? 'block' : 'hidden lg:block'">
                <div
                    class="rounded-lg border border-line bg-surface p-3.5 lg:border-0 lg:bg-transparent lg:p-0"
                >
                    <LessonToc content-selector="#lesson-body" />
                </div>
            </div>

            <!-- ─── ARTICLE ─────────────────────────────────── -->
            <article
                class="min-w-0 rounded-[10px] border border-line bg-surface px-5 py-6.5 sm:px-13 sm:py-11"
            >
                <p class="mono-label mb-2 text-[10px] tracking-[3px] text-ink-dis">
                    {{ lesson.module_title }}
                </p>

                <h1 class="mb-2 text-[34px] leading-[1.15] font-bold tracking-[-.4px] text-primary">
                    {{ lesson.title }}
                </h1>

                <p
                    v-if="lesson.description"
                    class="mb-3.5 border-b-[3px] border-primary pb-4 text-[17px] text-ink-sec"
                >
                    {{ lesson.description }}
                </p>

                <div class="mono-label mb-6 flex flex-wrap items-center gap-3 text-[10px] tracking-[1px] text-ink-dis">
                    <StatusPill
                        :label="state.completed ? 'Completed' : 'Not completed'"
                        :tone="state.completed ? 'positive' : 'neutral'"
                    />
                    <span>{{ lesson.type_label }}</span>
                    <span v-if="lesson.estimated_minutes">~{{ lesson.estimated_minutes }} min</span>
                </div>

                <!-- ═══ CONTENT BY TYPE ═════════════════════════ -->

                <!-- Rich text. v-html is safe here: the server ran the body
                     through HTMLPurifier's `lesson` allowlist before sending it,
                     which strips script, iframe, style and every event handler. -->
                <div
                    v-if="lesson.type === 'rich_text' && lesson.content"
                    id="lesson-body"
                    class="lesson-prose prose max-w-none"
                    v-html="lesson.content"
                ></div>

                <!-- PDF: streamed inline through the policy-checked route, never
                     a public storage URL. -->
                <div v-else-if="lesson.type === 'pdf' && primaryResource" id="lesson-body">
                    <iframe
                        :src="primaryResource.stream_url"
                        class="h-[70vh] w-full rounded-md border border-line"
                        :title="primaryResource.name"
                    ></iframe>
                </div>

                <div v-else-if="lesson.type === 'image' && primaryResource" id="lesson-body">
                    <img
                        :src="primaryResource.stream_url"
                        :alt="primaryResource.description || lesson.title"
                        class="max-w-full rounded-md border border-line"
                    />
                </div>

                <div v-else-if="lesson.type === 'external_link' && lesson.external_url" id="lesson-body">
                    <a
                        :href="lesson.external_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mono-label inline-block rounded-[5px] border border-primary bg-primary px-4 py-2.5 text-[11px] tracking-[1.5px] text-on-accent no-underline"
                    >
                        Open external resource ↗
                    </a>
                    <p class="mt-2 font-mono text-[11px] break-all text-ink-dis">
                        {{ lesson.external_url }}
                    </p>
                </div>

                <div v-else id="lesson-body">
                    <div
                        v-if="lesson.content"
                        class="lesson-prose prose max-w-none"
                        v-html="lesson.content"
                    ></div>
                    <p v-else class="text-[13px] text-ink-dis italic">
                        This lesson has no inline content — see the resources below.
                    </p>
                </div>

                <!-- ═══ RESOURCES ═══════════════════════════════ -->
                <div v-if="resources.length" class="mt-8 border-t border-line pt-5">
                    <h2 class="mono-label mb-3 text-[10px] tracking-[2px] text-ink-sec">
                        Resources
                    </h2>

                    <ul class="flex flex-col gap-2">
                        <li
                            v-for="resource in resources"
                            :key="resource.id"
                            class="flex items-center gap-3 rounded-md border border-line px-3 py-2.5"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-[13px] text-ink">{{ resource.name }}</div>
                                <div
                                    v-if="resource.description"
                                    class="truncate text-[11px] text-ink-sec"
                                >
                                    {{ resource.description }}
                                </div>
                            </div>

                            <span class="mono-label text-[9px] tracking-[1px] text-ink-dis">
                                {{ resource.size }}
                            </span>

                            <a
                                v-if="resource.is_downloadable"
                                :href="resource.download_url"
                                class="mono-label rounded border border-line px-2.5 py-1.5 text-[10px] tracking-[1px] text-ink-sec no-underline transition-colors hover:border-primary hover:text-primary"
                            >
                                Download
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- ═══ ATTACHED QUIZ ═══════════════════════════ -->
                <div
                    v-if="quiz"
                    class="mt-8 rounded-lg border px-4 py-3.5"
                    :class="quiz.passed ? 'border-positive bg-positive-bg' : 'border-warning-dim bg-warning-bg'"
                >
                    <div class="mb-1.5 flex items-center gap-2.5">
                        <span class="mono-label text-[10px] tracking-[2px] text-ink-sec">
                            Knowledge check
                        </span>
                        <StatusPill v-if="quiz.passed" label="Passed" tone="positive" />
                    </div>

                    <h3 class="mb-2 text-sm font-bold">{{ quiz.title }}</h3>

                    <Link
                        :href="route('quizzes.show', [course.slug, quiz.id])"
                        class="mono-label inline-block rounded-[5px] border border-primary bg-primary px-3.5 py-2 text-[11px] font-bold tracking-[1.5px] text-on-accent no-underline"
                    >
                        {{ quiz.passed ? 'Review' : 'Take quiz' }}
                    </Link>
                </div>

                <!-- ═══ COMPLETE ════════════════════════════════ -->
                <div
                    v-if="state.can_complete && lesson.completion_requirement === 'acknowledge'"
                    class="mt-8 flex items-center gap-3 border-t border-line pt-5"
                >
                    <button
                        type="button"
                        :disabled="saving"
                        class="mono-label cursor-pointer rounded-[5px] border px-4 py-2.5 text-[11px] font-bold tracking-[1.5px] transition-colors disabled:opacity-60"
                        :class="
                            state.completed
                                ? 'border-line bg-transparent text-ink-sec hover:border-ink-dis'
                                : 'border-primary bg-primary text-on-accent'
                        "
                        @click="toggleComplete"
                    >
                        {{ state.completed ? 'Mark as not complete' : 'Mark complete' }}
                    </button>
                </div>

                <p
                    v-else-if="lesson.completion_requirement === 'quiz'"
                    class="mt-8 border-t border-line pt-5 text-xs text-ink-dis italic"
                >
                    This lesson is completed by passing its quiz.
                </p>
            </article>
        </div>

        <!-- ─── PREV / NEXT ─────────────────────────────────── -->
        <div class="mx-auto flex max-w-[1220px] flex-wrap gap-3 px-5.5 pb-15">
            <Link
                v-if="navigation.previous"
                :href="navigation.previous.url"
                class="flex-1 rounded-lg border border-line bg-surface px-4 py-3 no-underline transition-colors hover:border-primary"
            >
                <span class="mono-label block text-[9px] tracking-[2px] text-ink-dis">Previous</span>
                <span class="text-[13px] text-ink">{{ navigation.previous.title }}</span>
            </Link>

            <Link
                v-if="navigation.next"
                :href="navigation.next.url"
                class="flex-1 rounded-lg border border-line bg-surface px-4 py-3 text-right no-underline transition-colors hover:border-primary"
            >
                <span class="mono-label block text-[9px] tracking-[2px] text-ink-dis">Next</span>
                <span class="text-[13px] text-ink">{{ navigation.next.title }}</span>
            </Link>
        </div>

        <AnnotationDrawer
            :open="drawerOpen"
            :annotations="annotations"
            @close="drawerOpen = false"
        />
    </EmployeeLayout>
</template>

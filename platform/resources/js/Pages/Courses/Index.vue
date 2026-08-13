<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from '@/lib/debounce';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import CourseCard from '@/Components/CourseCard.vue';
import EmptyState from '@/Components/EmptyState.vue';

const props = defineProps({
    courses: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
});

const search = ref(props.filters.search ?? '');
const category = ref(props.filters.category ?? '');
const status = ref(props.filters.status ?? '');

// Filtering happens in SQL, so each change is a request — debounced on the
// free-text field so typing does not fire one per keystroke.
const apply = debounce(() => {
    router.get(
        route('courses.index'),
        {
            search: search.value || undefined,
            category: category.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, category, status], apply);

const statuses = [
    { value: '', label: 'All statuses' },
    { value: 'not_started', label: 'Not started' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'failed', label: 'Failed' },
];
</script>

<template>
    <Head title="Courses" />

    <EmployeeLayout>
        <div class="mx-auto max-w-[1200px] px-6 py-10">
            <div class="mb-6">
                <p class="mono-label mb-1.5 text-[11px] tracking-[3px] text-primary">Catalogue</p>
                <h1 class="text-xl font-bold">Your courses</h1>
            </div>

            <!-- ─── FILTERS ─────────────────────────────────── -->
            <div class="mb-8 flex flex-wrap gap-2.5">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search courses…"
                    aria-label="Search courses"
                    class="min-w-[220px] flex-1 rounded-[5px] border border-line bg-surface px-3 py-2 font-mono text-[12px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none"
                />

                <select
                    v-model="category"
                    aria-label="Filter by category"
                    class="mono-label cursor-pointer rounded-[5px] border border-line bg-surface px-3 py-2 text-[10px] tracking-[1px] text-ink-sec focus:border-primary focus:outline-none"
                >
                    <option value="">All categories</option>
                    <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
                </select>

                <select
                    v-model="status"
                    aria-label="Filter by status"
                    class="mono-label cursor-pointer rounded-[5px] border border-line bg-surface px-3 py-2 text-[10px] tracking-[1px] text-ink-sec focus:border-primary focus:outline-none"
                >
                    <option v-for="s in statuses" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </option>
                </select>
            </div>

            <div v-if="courses.length" class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <CourseCard
                    v-for="(course, i) in courses"
                    :key="course.id"
                    :course="course"
                    :index="i"
                />
            </div>

            <EmptyState
                v-else
                icon="🔍"
                title="No courses match"
                :description="
                    filters.search || filters.category || filters.status
                        ? 'Try clearing a filter.'
                        : 'You have not been assigned any training yet.'
                "
            />
        </div>
    </EmployeeLayout>
</template>

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

const field =
    'rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';
</script>

<template>
    <Head title="Courses" />

    <EmployeeLayout>
        <div class="mb-6">
            <h1 class="mb-1 text-2xl font-extrabold text-navy">Your courses</h1>
            <p class="text-sm text-ink-sec">
                Everything assigned to you, plus anything you have opted into.
            </p>
        </div>

        <!-- ─── FILTERS ─────────────────────────────────────── -->
        <div class="mb-8 flex flex-wrap gap-3">
            <input
                v-model="search"
                type="search"
                placeholder="Search courses…"
                aria-label="Search courses"
                :class="field"
                class="min-w-[240px] flex-1"
            />

            <select v-model="category" aria-label="Filter by category" :class="field" class="cursor-pointer">
                <option value="">All categories</option>
                <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
            </select>

            <select v-model="status" aria-label="Filter by status" :class="field" class="cursor-pointer">
                <option v-for="s in statuses" :key="s.value" :value="s.value">
                    {{ s.label }}
                </option>
            </select>
        </div>

        <div v-if="courses.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <CourseCard v-for="course in courses" :key="course.id" :course="course" />
        </div>

        <EmptyState
            v-else
            title="No courses match"
            :description="
                filters.search || filters.category || filters.status
                    ? 'Try clearing a filter.'
                    : 'You have not been assigned any training yet.'
            "
        />
    </EmployeeLayout>
</template>

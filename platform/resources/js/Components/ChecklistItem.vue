<script setup>
/**
 * A checklist row on the tracker.
 *
 * The tick is optimistic so it feels immediate, but the server owns the truth —
 * if the request fails the box goes back, because a checkbox that claims "done"
 * while the database disagrees is worse than one that is briefly slow.
 */
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    item: { type: Object, required: true },
    courseSlug: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});

const checked = ref(props.item.completed);
const saving = ref(false);

watch(
    () => props.item.completed,
    (value) => (checked.value = value),
);

function toggle() {
    if (props.disabled || saving.value) return;

    const previous = checked.value;
    checked.value = !previous;
    saving.value = true;

    router.visit(
        route(previous ? 'lessons.uncomplete' : 'lessons.complete', [
            props.courseSlug,
            props.item.slug,
        ]),
        {
            method: previous ? 'delete' : 'post',
            preserveScroll: true,
            onError: () => {
                checked.value = previous;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-lg px-2 py-2 transition-colors hover:bg-surface-alt"
        :class="disabled ? '' : 'cursor-pointer'"
        @click="toggle"
    >
        <button
            type="button"
            role="checkbox"
            :aria-checked="checked"
            :aria-label="item.title"
            :disabled="disabled"
            class="mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-md border-2 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"
            :class="[
                checked ? 'border-ok bg-ok' : 'border-line-strong bg-transparent hover:border-brand',
                saving ? 'opacity-60' : '',
            ]"
            @click.stop="toggle"
        >
            <svg
                v-if="checked"
                class="h-3 w-3 fill-none stroke-white stroke-[3]"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </button>

        <a
            v-if="item.url"
            :href="item.url"
            class="flex-1 text-sm leading-snug no-underline transition-colors"
            :class="checked ? 'text-ink-dis line-through' : 'text-ink hover:text-brand'"
            @click.stop
        >
            {{ item.title }}
        </a>
        <span
            v-else
            class="flex-1 text-sm leading-snug"
            :class="checked ? 'text-ink-dis line-through' : 'text-ink'"
        >
            {{ item.title }}
        </span>
    </div>
</template>

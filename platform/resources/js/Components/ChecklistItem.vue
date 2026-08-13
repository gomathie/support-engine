<script setup>
/**
 * The tracker's toggle-switch checklist row.
 *
 * Visually identical to the prototype: a 16px rounded square that fills with
 * primary and grows a CSS check when ticked, and text that greys out and
 * strikes through.
 *
 * Behaviourally different in one important way. The prototype flipped
 * `STATE[key]` and believed itself. Here the tick is optimistic for
 * responsiveness but the server owns the truth — if the request fails the box
 * goes back, because a checkbox that says "done" when the database disagrees is
 * worse than one that is briefly slow.
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

    const url = route(previous ? 'lessons.uncomplete' : 'lessons.complete', [
        props.courseSlug,
        props.item.slug,
    ]);

    router.visit(url, {
        method: previous ? 'delete' : 'post',
        preserveScroll: true,
        onError: () => {
            checked.value = previous;
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}
</script>

<template>
    <div
        class="flex items-start gap-[9px] py-[3px]"
        :class="disabled ? 'cursor-default' : 'cursor-pointer'"
    >
        <button
            type="button"
            role="checkbox"
            :aria-checked="checked"
            :aria-label="item.title"
            :disabled="disabled"
            class="relative mt-px h-4 w-4 shrink-0 rounded-[3px] border-[1.5px] transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            :class="[
                checked ? 'border-primary bg-primary' : 'border-ink-dis bg-transparent',
                saving ? 'opacity-60' : '',
            ]"
            @click.stop="toggle"
        >
            <span
                v-if="checked"
                class="absolute top-0 left-[4px] h-2 w-1 rotate-45 border-r-2 border-b-2 border-surface"
            ></span>
        </button>

        <component
            :is="item.url ? 'a' : 'span'"
            :href="item.url"
            class="text-[13px] leading-snug no-underline"
            :class="
                checked
                    ? 'text-ink-dis line-through'
                    : 'text-ink hover:text-primary'
            "
        >
            {{ item.title }}
        </component>
    </div>
</template>

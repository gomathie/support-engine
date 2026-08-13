<script setup>
/** The thin section bar under each tracker heading: 4px track, primary fill. */
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, default: 0 },
    tone: { type: String, default: 'primary' },
    height: { type: String, default: 'h-1' },
});

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));

const fill = computed(
    () =>
        ({
            primary: 'bg-primary',
            positive: 'bg-positive',
            negative: 'bg-negative',
            warning: 'bg-warning',
            neutral: 'bg-ink-dis',
        })[props.tone] ?? 'bg-primary',
);
</script>

<template>
    <div
        class="overflow-hidden rounded-sm bg-line"
        :class="height"
        role="progressbar"
        :aria-valuenow="Math.round(clamped)"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <div
            class="h-full transition-[width] duration-500 ease-out"
            :class="fill"
            :style="{ width: `${clamped}%` }"
        ></div>
    </div>
</template>

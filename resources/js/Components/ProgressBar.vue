<script setup>
/** The academy's progress bar: 2 units tall, fully rounded, slate track. */
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, default: 0 },
    tone: { type: String, default: 'primary' },
    height: { type: String, default: 'h-2' },
});

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));

const fill = computed(
    () =>
        ({
            primary: 'bg-brand',
            positive: 'bg-ok',
            negative: 'bg-negative',
            warning: 'bg-warning',
            neutral: 'bg-ink-dis',
        })[props.tone] ?? 'bg-brand',
);
</script>

<template>
    <div
        class="overflow-hidden rounded-full bg-surface-alt"
        :class="height"
        role="progressbar"
        :aria-valuenow="Math.round(clamped)"
        aria-valuemin="0"
        aria-valuemax="100"
    >
        <div
            class="h-full rounded-full transition-[width] duration-500 ease-out"
            :class="fill"
            :style="{ width: `${clamped}%` }"
        ></div>
    </div>
</template>

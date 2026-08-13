<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    tone: { type: String, default: 'neutral' },
});

const classes = computed(
    () =>
        ({
            positive: 'border-positive bg-positive-bg text-positive',
            negative: 'border-negative bg-negative-bg text-negative',
            warning: 'border-warning-dim bg-warning-bg text-warning-dim dark:text-warning',
            primary: 'border-primary bg-primary-bg text-primary',
            neutral: 'border-line bg-surface-alt text-ink-sec',
        })[props.tone] ?? 'border-line bg-surface-alt text-ink-sec',
);
</script>

<template>
    <span
        class="mono-label inline-flex items-center rounded-[3px] border px-2 py-0.5 text-[9px] tracking-[1.5px] whitespace-nowrap"
        :class="classes"
    >
        {{ label }}
    </span>
</template>

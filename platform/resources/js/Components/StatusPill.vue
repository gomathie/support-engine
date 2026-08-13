<script setup>
/** The academy's chip: tiny, uppercase, wide-tracked, on a tinted pill. */
import { computed } from 'vue';

const props = defineProps({
    label: { type: String, required: true },
    tone: { type: String, default: 'neutral' },
});

const classes = computed(
    () =>
        ({
            positive: 'bg-positive-bg text-positive',
            negative: 'bg-negative-bg text-negative',
            warning: 'bg-warning-bg text-warning-dim dark:text-warning',
            primary: 'bg-brand-soft text-brand',
            accent: 'bg-violet-50 text-violet-600 dark:bg-violet-950 dark:text-violet-300',
            neutral: 'bg-surface-alt text-ink-sec',
        })[props.tone] ?? 'bg-surface-alt text-ink-sec',
);
</script>

<template>
    <span class="chip" :class="classes">{{ label }}</span>
</template>

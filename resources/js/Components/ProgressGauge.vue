<script setup>
/**
 * Circular progress ring. r=42 in a 100×100 box gives a circumference of ~264,
 * which is the stroke-dasharray; the arc is drawn by moving stroke-dashoffset
 * from 264 (empty) to 0 (full), rotated -90° so it starts at twelve o'clock.
 *
 * The value comes from the server, not from counting ticked boxes in the page.
 */
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, default: 0 },
    size: { type: Number, default: 96 },
    label: { type: String, default: 'complete' },
});

const CIRCUMFERENCE = 264;

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));
const offset = computed(() => CIRCUMFERENCE - (clamped.value / 100) * CIRCUMFERENCE);

const stroke = computed(() => (clamped.value >= 100 ? 'var(--color-ok)' : 'var(--color-brand)'));
</script>

<template>
    <div class="relative shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg viewBox="0 0 100 100" class="h-full w-full">
            <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                stroke="var(--color-surface-alt)"
                stroke-width="8"
            />
            <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                :stroke="stroke"
                stroke-width="8"
                :stroke-dasharray="CIRCUMFERENCE"
                :stroke-dashoffset="offset"
                stroke-linecap="round"
                transform="rotate(-90 50 50)"
                class="transition-[stroke-dashoffset] duration-500 ease-out"
            />
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-xl leading-none font-extrabold text-navy">
                {{ Math.round(clamped) }}%
            </span>
            <span class="mt-0.5 text-[10px] font-medium text-ink-dis">{{ label }}</span>
        </div>
    </div>
</template>

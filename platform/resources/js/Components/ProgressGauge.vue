<script setup>
/**
 * The circular gauge from the Training Tracker header.
 *
 * Geometry is unchanged from the prototype: r=42 in a 100x100 box, giving a
 * circumference of ~264, which is the stroke-dasharray. The arc is drawn by
 * moving stroke-dashoffset from 264 (empty) to 0 (full) and rotating -90deg so
 * it starts at twelve o'clock.
 *
 * The difference is where the number comes from: the server, not a count of
 * ticked boxes in the browser.
 */
import { computed } from 'vue';

const props = defineProps({
    percentage: { type: Number, default: 0 },
    size: { type: Number, default: 108 },
    label: { type: String, default: 'complete' },
});

const CIRCUMFERENCE = 264;

const clamped = computed(() => Math.min(100, Math.max(0, props.percentage)));
const offset = computed(() => CIRCUMFERENCE - (clamped.value / 100) * CIRCUMFERENCE);
</script>

<template>
    <div class="relative shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
        <svg viewBox="0 0 100 100" class="h-full w-full">
            <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                stroke="var(--color-line-strong)"
                stroke-width="7"
            />
            <circle
                cx="50"
                cy="50"
                r="42"
                fill="none"
                stroke="var(--color-warning)"
                stroke-width="7"
                :stroke-dasharray="CIRCUMFERENCE"
                :stroke-dashoffset="offset"
                stroke-linecap="round"
                transform="rotate(-90 50 50)"
                class="transition-[stroke-dashoffset] duration-500 ease-out"
            />
        </svg>

        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="font-mono text-[22px] leading-none font-bold tracking-[-1px] text-warning">
                {{ Math.round(clamped) }}%
            </span>
            <span class="mono-label mt-0.5 text-[9px] tracking-[2px] text-ink-dis">
                {{ label }}
            </span>
        </div>
    </div>
</template>

<script setup>
/** A single figure on the dashboard: big navy number, quiet slate label. */
defineProps({
    value: { type: [Number, String], required: true },
    label: { type: String, required: true },
    suffix: { type: String, default: '' },
    tone: { type: String, default: 'default' },
});
</script>

<template>
    <div class="card px-5 py-4">
        <div
            class="text-2xl leading-none font-extrabold"
            :class="
                {
                    positive: 'text-ok',
                    negative: 'text-negative',
                    brand: 'text-brand',
                    default: 'text-navy',
                }[tone]
            "
        >
            {{ value }}<span v-if="suffix" class="text-lg">{{ suffix }}</span>
        </div>
        <div class="mt-1.5 text-xs font-medium text-ink-sec">{{ label }}</div>
    </div>
</template>

<script setup>
/**
 * One cell of the landing page's stats bar. The prototype hard-coded
 * "3 Modules / 2 Week Program / 4 Skill Areas / 7 Diagnostic Layers"; these
 * are real aggregates, but the treatment — big mono number in amber, tiny
 * wide-tracked uppercase label — is unchanged.
 */
defineProps({
    value: { type: [Number, String], required: true },
    label: { type: String, required: true },
    suffix: { type: String, default: '' },
});
</script>

<template>
    <div class="text-center">
        <div class="font-mono text-[28px] font-bold tracking-[-1px] text-[#a16207] dark:text-warning">
            {{ value }}<span v-if="suffix" class="text-lg">{{ suffix }}</span>
        </div>
        <div class="mono-label mt-1 text-[10px] tracking-[2px] text-ink-sec">
            {{ label }}
        </div>
    </div>
</template>

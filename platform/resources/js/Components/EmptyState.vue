<script setup>
defineProps({
    icon: { type: String, default: '—' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-lg border border-dashed border-line bg-surface px-6 py-12 text-center">
        <div class="mb-3 text-3xl opacity-40">{{ icon }}</div>
        <h3 class="mb-1.5 text-sm font-bold text-ink">{{ title }}</h3>
        <p v-if="description" class="mx-auto max-w-md text-xs leading-relaxed text-ink-sec">
            {{ description }}
        </p>
        <div class="mt-4">
            <slot />
        </div>
    </div>
</template>

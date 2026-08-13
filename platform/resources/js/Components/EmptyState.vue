<script setup>
defineProps({
    icon: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
});
</script>

<template>
    <div class="card border-dashed px-6 py-14 text-center">
        <div v-if="icon" class="mb-3 text-3xl opacity-40">{{ icon }}</div>
        <h3 class="mb-1.5 text-base font-bold text-navy">{{ title }}</h3>
        <p v-if="description" class="mx-auto max-w-md text-sm leading-relaxed text-ink-sec">
            {{ description }}
        </p>
        <div class="mt-5">
            <slot />
        </div>
    </div>
</template>

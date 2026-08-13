<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const visible = ref(true);

const flash = computed(() => page.props.flash ?? {});
const message = computed(() => flash.value.success || flash.value.error);
const isError = computed(() => Boolean(flash.value.error));

watch(message, (value) => {
    visible.value = Boolean(value);

    if (value) {
        setTimeout(() => (visible.value = false), 5000);
    }
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="-translate-y-2 opacity-0"
        leave-active-class="transition duration-150 ease-in"
        leave-to-class="-translate-y-2 opacity-0"
    >
        <div
            v-if="message && visible"
            class="fixed top-20 right-5 z-30 max-w-sm rounded-xl border px-4 py-3 shadow-lg"
            :class="
                isError
                    ? 'border-negative bg-negative-bg text-negative'
                    : 'border-ok bg-positive-bg text-ok'
            "
            role="status"
            aria-live="polite"
        >
            <p class="text-sm leading-relaxed font-medium">{{ message }}</p>
        </div>
    </Transition>
</template>

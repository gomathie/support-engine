<script setup>
/**
 * The prototype's only feedback was a "saved 14:32:07" text stamp that lied —
 * the write it reported had actually thrown. These are real server
 * confirmations, flashed from the session after a successful write.
 */
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
            class="fixed top-[66px] right-6 z-100 max-w-sm rounded-md border px-4 py-3 shadow-lg"
            :class="
                isError
                    ? 'border-negative bg-negative-bg text-negative'
                    : 'border-positive bg-positive-bg text-positive'
            "
            role="status"
            aria-live="polite"
        >
            <p class="text-xs leading-relaxed">{{ message }}</p>
        </div>
    </Transition>
</template>

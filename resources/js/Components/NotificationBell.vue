<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);
const root = ref(null);

const notifications = computed(() => page.props.notifications ?? []);
const count = computed(() => notifications.value.length);

function markRead(id) {
    router.post(route('notifications.read', id), {}, { preserveScroll: true });
}

function markAllRead() {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
    open.value = false;
}

function relative(iso) {
    const seconds = Math.floor((Date.now() - new Date(iso)) / 1000);
    if (seconds < 60) return 'just now';
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
    return `${Math.floor(seconds / 86400)}d ago`;
}

// Close on click-out and Escape, matching the skills-module drawer.
function onDocumentClick(event) {
    if (root.value && !root.value.contains(event.target)) open.value = false;
}

function onKeydown(event) {
    if (event.key === 'Escape') open.value = false;
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="relative flex cursor-pointer items-center justify-center rounded-lg p-2 text-ink-sec transition-colors hover:bg-nav-hover hover:text-brand"
            :aria-label="`Notifications${count ? ` (${count} unread)` : ''}`"
            :aria-expanded="open"
            @click="open = !open"
        >
            <svg class="h-[18px] w-[18px] fill-current" viewBox="0 0 24 24">
                <path
                    d="M12 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 006 14h12a1 1 0 00.707-1.707L18 11.586V8a6 6 0 00-6-6zM12 22a2.5 2.5 0 002.45-2h-4.9A2.5 2.5 0 0012 22z"
                />
            </svg>

            <span
                v-if="count"
                class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-negative px-1 font-mono text-[9px] font-bold text-white"
            >
                {{ count > 9 ? '9+' : count }}
            </span>
        </button>

        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="-translate-y-1 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="-translate-y-1 opacity-0"
        >
            <div
                v-if="open"
                class="card absolute right-0 z-50 mt-2 w-80 overflow-hidden shadow-xl"
                role="dialog"
                aria-label="Notifications"
            >
                <div class="flex items-center gap-2 border-b border-line bg-surface-alt px-4 py-3">
                    <span class="flex-1 text-sm font-bold text-navy">Notifications</span>
                    <button
                        v-if="count"
                        type="button"
                        class="cursor-pointer text-xs font-semibold text-brand"
                        @click="markAllRead"
                    >
                        Mark all read
                    </button>
                </div>

                <div class="max-h-80 overflow-auto">
                    <div
                        v-for="notification in notifications"
                        :key="notification.id"
                        class="border-b border-line px-4 py-3 last:border-b-0 hover:bg-surface-alt"
                    >
                        <component
                            :is="notification.url ? Link : 'div'"
                            :href="notification.url"
                            class="block no-underline"
                            @click="markRead(notification.id)"
                        >
                            <div class="flex items-start gap-2">
                                <span
                                    class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full"
                                    :class="
                                        notification.type === 'training_overdue'
                                            ? 'bg-negative'
                                            : 'bg-brand'
                                    "
                                ></span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-semibold text-navy">
                                        {{ notification.title }}
                                    </div>
                                    <div class="truncate text-sm text-ink-sec">
                                        {{ notification.body }}
                                    </div>
                                    <div class="mt-1 text-xs text-ink-dis">
                                        {{ relative(notification.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </component>
                    </div>

                    <p v-if="!count" class="px-4 py-8 text-center text-sm text-ink-dis">
                        Nothing new.
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>

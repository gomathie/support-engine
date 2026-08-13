<script setup>
/**
 * The slide-in "standard defaults" drawer from the skills module.
 *
 * The prototype built this list at runtime by scanning the DOM for `.std`
 * elements and inferring each one's section by walking previous siblings until
 * it hit a heading. That worked but was fragile and unauthored — nobody could
 * edit an entry. The list now arrives as rows from lesson_annotations.
 *
 * The interaction is preserved exactly: click to open, click the backdrop or
 * press Escape to close, click an entry to scroll the marker into view and
 * flash it.
 */
import { computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    annotations: { type: Array, default: () => [] },
});

const emit = defineEmits(['close']);

const unresolved = computed(() => props.annotations.filter((a) => !a.is_resolved));

function jumpTo(anchor) {
    emit('close');

    // The anchor ids inside lesson HTML are namespaced by HTMLPurifier's
    // Attr.IDPrefix, so look for the prefixed id first.
    const el =
        document.getElementById(`lesson-${anchor}`) || document.getElementById(anchor);

    if (!el) return;

    el.scrollIntoView({ block: 'center', behavior: 'smooth' });
    el.classList.remove('flash');
    void el.offsetWidth; // force reflow so the animation restarts
    el.classList.add('flash');
}

function onKeydown(event) {
    if (event.key === 'Escape') emit('close');
}

onMounted(() => document.addEventListener('keydown', onKeydown));
onUnmounted(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        leave-active-class="transition-opacity duration-150"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-100 bg-navy/50"
            @click.self="emit('close')"
        >
            <div
                class="absolute top-0 right-0 bottom-0 flex w-[min(460px,100%)] flex-col bg-surface shadow-[-8px_0_30px_rgba(0,0,0,.5)]"
                role="dialog"
                aria-modal="true"
                aria-label="Standard defaults"
            >
                <div class="flex items-center gap-2.5 border-b border-line bg-surface-alt px-5 py-4">
                    <h3 class="text-base font-bold text-navy">Standard defaults</h3>
                    <span class="chip bg-warning-bg text-warning-dim dark:text-warning">
                        {{ unresolved.length }}
                    </span>
                    <div class="flex-1"></div>
                    <button
                        type="button"
                        class="cursor-pointer rounded-lg px-2.5 py-1 text-lg leading-none text-ink-dis transition-colors hover:bg-surface hover:text-ink"
                        aria-label="Close"
                        @click="emit('close')"
                    >
                        ×
                    </button>
                </div>

                <div class="overflow-auto px-5 pt-4 pb-6">
                    <p class="mb-4 text-sm leading-relaxed text-ink-sec">
                        Industry-standard answers proposed by the trainer. Confirm each one
                        locally, and escalate anything unresolved.
                    </p>

                    <button
                        v-for="annotation in unresolved"
                        :key="annotation.id"
                        type="button"
                        class="block w-full cursor-pointer rounded-lg border-b border-line px-2 py-3 text-left text-sm leading-snug text-ink transition-colors hover:bg-surface-alt focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand"
                        @click="jumpTo(annotation.anchor)"
                    >
                        <span
                            v-if="annotation.section_label"
                            class="mb-1 block text-xs font-semibold tracking-wide text-ink-dis uppercase"
                        >
                            {{ annotation.section_label }}
                        </span>
                        {{ annotation.body }}
                    </button>

                    <p v-if="!unresolved.length" class="text-sm text-ink-dis italic">
                        Nothing outstanding in this lesson.
                    </p>
                </div>
            </div>
        </div>
    </Transition>
</template>

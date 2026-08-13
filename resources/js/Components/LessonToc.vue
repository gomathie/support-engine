<script setup>
/**
 * The sticky table of contents with scroll-spy from the skills module.
 *
 * Same IntersectionObserver approach and the same rootMargin
 * ('-70px 0px -75% 0px'), which is what makes the highlight land on the heading
 * nearest the top of the viewport rather than any heading merely on screen.
 *
 * Headings are discovered from the rendered lesson body rather than authored
 * separately, so a trainer editing content never has to maintain this list.
 */
import { nextTick, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    contentSelector: { type: String, default: '#lesson-body' },
});

const headings = ref([]);
const activeId = ref(null);

let observer = null;

onMounted(async () => {
    await nextTick();

    const root = document.querySelector(props.contentSelector);
    if (!root) return;

    const nodes = [...root.querySelectorAll('h2[id], h3[id]')];

    headings.value = nodes.map((node) => ({
        id: node.id,
        text: node.textContent.trim(),
        level: Number(node.tagName[1]),
    }));

    if (!nodes.length) return;

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) activeId.value = entry.target.id;
            });
        },
        { rootMargin: '-70px 0px -75% 0px' },
    );

    nodes.forEach((node) => observer.observe(node));
});

onUnmounted(() => observer?.disconnect());

function jump(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}
</script>

<template>
    <nav v-if="headings.length" class="sticky top-24 max-h-[calc(100vh-8rem)] overflow-auto pr-1.5">
        <p class="mb-2 text-xs font-semibold tracking-wide text-ink-dis uppercase">Contents</p>

        <button
            v-for="heading in headings"
            :key="heading.id"
            type="button"
            class="block w-full cursor-pointer border-l-2 py-1 pr-0 pl-3 text-left text-sm leading-snug transition-colors"
            :class="[
                activeId === heading.id
                    ? 'border-brand font-semibold text-brand'
                    : 'border-line text-ink-sec hover:border-brand hover:text-brand',
                heading.level === 3 ? 'pl-6' : '',
            ]"
            @click="jump(heading.id)"
        >
            {{ heading.text }}
        </button>
    </nav>
</template>

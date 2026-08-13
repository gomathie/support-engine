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
    <nav v-if="headings.length" class="sticky top-[128px] max-h-[calc(100vh-96px)] overflow-auto pr-1.5">
        <p class="mono-label mb-1.5 text-[9.5px] tracking-[1.6px] text-ink-dis">Contents</p>

        <button
            v-for="heading in headings"
            :key="heading.id"
            type="button"
            class="block w-full cursor-pointer border-l-2 py-0.5 pr-0 pl-2.5 text-left text-[12.5px] leading-snug transition-colors"
            :class="[
                activeId === heading.id
                    ? 'border-primary font-semibold text-primary'
                    : 'border-line text-ink-dis hover:border-primary hover:text-primary',
                heading.level === 3 ? 'pl-5' : '',
            ]"
            @click="jump(heading.id)"
        >
            {{ heading.text }}
        </button>
    </nav>
</template>

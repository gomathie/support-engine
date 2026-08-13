<script setup>
/**
 * The Support Panel.
 *
 * The earlier version dropped an agent straight into a flat list of symptoms
 * with no explanation of what the tool was or what the buttons did. This one
 * makes the method explicit: pick what the customer said, work the checks top
 * down, and the case note assembles itself from what you rule out.
 *
 * The tri-state per step, the reveal-on-found fix text, the priority matrix and
 * the clipboard copy with its execCommand fallback are all carried over from
 * the prototype. The case is a persisted row rather than an object that
 * evaporated on reload.
 */
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from '@/lib/debounce';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

const props = defineProps({
    trees: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    layers: { type: Array, default: () => [] },
    case: { type: Object, default: null },
    priority_matrix: { type: Array, default: () => [] },
});

const selectedTreeId = ref(props.case?.diagnostic_tree_id ?? null);
const customer = ref(props.case?.customer ?? '');
const objectRef = ref(props.case?.object_ref ?? '');
const stepStates = ref({ ...(props.case?.step_states ?? {}) });
const priority = ref(
    props.case?.priority_code
        ? { code: props.case.priority_code, reason: props.case.priority_reason }
        : null,
);
const customerTold = ref(props.case?.customer_told ?? '');

const stamp = ref('');
const search = ref('');
const showLayers = ref(false);

const activeTree = computed(
    () => props.trees.find((tree) => tree.id === selectedTreeId.value) ?? null,
);

const note = computed(() => props.case?.note ?? 'Nothing recorded yet.');

// ─── SYMPTOM PICKER ───────────────────────────────────────
const filteredTrees = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.trees;

    return props.trees.filter(
        (t) =>
            t.question.toLowerCase().includes(q) ||
            (t.category ?? '').toLowerCase().includes(q) ||
            t.steps.some((s) => s.prompt.toLowerCase().includes(q)),
    );
});

const groupedTrees = computed(() =>
    props.categories
        .map((category) => ({
            category,
            trees: filteredTrees.value.filter((t) => t.category === category),
        }))
        .filter((group) => group.trees.length > 0),
);

// ─── PROGRESS THROUGH THE TREE ────────────────────────────
const worked = computed(() =>
    activeTree.value
        ? activeTree.value.steps.filter((s) => stepState(s.id) !== null).length
        : 0,
);

const foundCause = computed(() =>
    activeTree.value
        ? activeTree.value.steps.filter((s) => stepState(s.id) === 'found')
        : [],
);

const allWorkedNoCause = computed(
    () =>
        activeTree.value &&
        worked.value === activeTree.value.steps.length &&
        foundCause.value.length === 0,
);

// ─── PERSISTENCE ──────────────────────────────────────────
const persist = debounce(() => {
    if (!props.case) return;

    router.put(
        route('support-cases.update', props.case.id),
        {
            diagnostic_tree_id: selectedTreeId.value,
            customer: customer.value,
            object_ref: objectRef.value,
            step_states: stepStates.value,
            priority_code: priority.value?.code ?? null,
            priority_reason: priority.value?.reason ?? null,
            customer_told: customerTold.value,
        },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => (stamp.value = `Saved ${new Date().toLocaleTimeString()}`),
            onError: () => (stamp.value = 'Not saved'),
        },
    );
}, 600);

watch([selectedTreeId, customer, objectRef, stepStates, priority, customerTold], persist, {
    deep: true,
});

function selectSymptom(tree) {
    if (selectedTreeId.value === tree.id) return;
    selectedTreeId.value = tree.id;
    stepStates.value = {};
}

function clearSymptom() {
    selectedTreeId.value = null;
    stepStates.value = {};
}

function markStep(stepId, value) {
    const key = String(stepId);
    if (stepStates.value[key] === value) {
        delete stepStates.value[key];
    } else {
        stepStates.value[key] = value;
    }
    stepStates.value = { ...stepStates.value };
}

const stepState = (stepId) => stepStates.value[String(stepId)] ?? null;

function selectPriority(code, rowLabel, columnIndex) {
    const urgency = ['high', 'medium', 'low'][columnIndex];
    const reason = `${rowLabel}, ${urgency} urgency`;

    priority.value =
        priority.value?.code === code && priority.value?.reason === reason
            ? null
            : { code, reason };
}

function newCase() {
    router.post(route('support-cases.store'), {}, { preserveScroll: true });
}

async function copyNote() {
    try {
        await navigator.clipboard.writeText(note.value);
        stamp.value = 'Copied to clipboard';
    } catch {
        const ta = document.createElement('textarea');
        ta.value = note.value;
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            stamp.value = 'Copied to clipboard';
        } catch {
            stamp.value = 'Select the text above to copy';
        }
        ta.remove();
    }
}

const priorityColour = (code) =>
    ({
        P1: 'text-negative',
        P2: 'text-warning-dim dark:text-warning',
        P3: 'text-amber-500',
        P4: 'text-brand',
        P5: 'text-ink-dis',
    })[code] ?? 'text-ink';

const fieldClass =
    'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';
</script>

<template>
    <Head title="Support Panel" />

    <EmployeeLayout>
        <!-- ─── HOW IT WORKS ────────────────────────────────── -->
        <div class="mb-6">
            <h1 class="mb-1 text-2xl font-extrabold text-navy">Support panel</h1>
            <p class="mb-5 max-w-2xl text-sm leading-relaxed text-ink-sec">
                A guided diagnostic for live calls. Faults sit near the top of these lists far more
                often than the bottom, so work them in order rather than jumping to the device.
            </p>

            <ol class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <li
                    v-for="(step, i) in [
                        { t: 'Pick what they said', d: 'In the customer\'s words, not yours.' },
                        { t: 'Work the checks in order', d: 'Rule each one out, or mark it as the cause.' },
                        { t: 'Copy the case note', d: 'It writes itself as you go. Paste it into the ticket.' },
                    ]"
                    :key="i"
                    class="card flex gap-3 p-4"
                >
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-bold text-white"
                    >
                        {{ i + 1 }}
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-navy">{{ step.t }}</span>
                        <span class="block text-xs leading-snug text-ink-sec">{{ step.d }}</span>
                    </span>
                </li>
            </ol>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_340px]">
            <main class="min-w-0">
                <!-- ═══ STEP 1 — PICK A SYMPTOM ═════════════════ -->
                <section v-if="!activeTree">
                    <div class="mb-4 flex flex-wrap items-center gap-3">
                        <h2 class="flex-1 text-lg font-bold text-navy">
                            What did the customer say?
                        </h2>
                        <input
                            v-model="search"
                            type="search"
                            placeholder="Search symptoms and checks…"
                            aria-label="Search symptoms"
                            :class="fieldClass"
                            class="max-w-xs"
                        />
                    </div>

                    <div v-for="group in groupedTrees" :key="group.category" class="mb-6">
                        <h3 class="mb-2 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                            {{ group.category }}
                        </h3>

                        <div class="flex flex-col gap-2">
                            <button
                                v-for="tree in group.trees"
                                :key="tree.id"
                                type="button"
                                class="card card-interactive flex w-full cursor-pointer items-start gap-3 p-4 text-left"
                                @click="selectSymptom(tree)"
                            >
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-semibold text-navy">
                                        “{{ tree.question }}”
                                    </span>
                                    <span
                                        v-if="tree.description"
                                        class="mt-0.5 block text-xs leading-snug text-ink-sec"
                                    >
                                        {{ tree.description }}
                                    </span>
                                </span>

                                <span class="chip shrink-0 bg-surface-alt text-ink-sec">
                                    L{{ tree.layer_label }}
                                </span>
                                <span class="chip shrink-0 bg-brand-soft text-brand">
                                    {{ tree.steps.length }} checks
                                </span>
                            </button>
                        </div>
                    </div>

                    <p v-if="!groupedTrees.length" class="text-sm text-ink-dis italic">
                        Nothing matches “{{ search }}”.
                    </p>
                </section>

                <!-- ═══ STEP 2 — WORK THE CHECKS ════════════════ -->
                <section v-else>
                    <button
                        type="button"
                        class="mb-4 cursor-pointer text-sm font-medium text-ink-sec hover:text-brand"
                        @click="clearSymptom"
                    >
                        ← Choose a different symptom
                    </button>

                    <div class="card mb-4 p-5">
                        <p class="mb-1 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                            {{ activeTree.category }} · layers {{ activeTree.layer_label }}
                        </p>
                        <h2 class="mb-2 text-lg font-bold text-navy">
                            “{{ activeTree.question }}”
                        </h2>
                        <p v-if="activeTree.description" class="mb-4 text-sm leading-relaxed text-ink-sec">
                            {{ activeTree.description }}
                        </p>

                        <div class="flex items-center gap-3">
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-alt">
                                <div
                                    class="h-full rounded-full transition-[width] duration-300"
                                    :class="foundCause.length ? 'bg-ok' : 'bg-brand'"
                                    :style="{
                                        width: `${(worked / activeTree.steps.length) * 100}%`,
                                    }"
                                ></div>
                            </div>
                            <span class="shrink-0 text-sm font-medium text-ink-sec">
                                {{ worked }} of {{ activeTree.steps.length }} worked
                            </span>
                        </div>
                    </div>

                    <!-- Outcome banners -->
                    <div
                        v-if="foundCause.length"
                        class="mb-4 rounded-xl border border-ok/40 bg-positive-bg px-5 py-4"
                    >
                        <p class="text-sm font-bold text-ok">Cause identified</p>
                        <p class="mt-1 text-sm leading-relaxed text-ink-sec">
                            Fix it, tell the customer what happened, and if you changed a formula or
                            a calibration table remember to run Recalculate for the affected period.
                        </p>
                    </div>

                    <div
                        v-else-if="allWorkedNoCause"
                        class="mb-4 rounded-xl border border-warning-dim/40 bg-warning-bg px-5 py-4"
                    >
                        <p class="text-sm font-bold text-warning-dim dark:text-warning">
                            Every check worked, no cause found → escalate
                        </p>
                        <p class="mt-1 text-sm leading-relaxed text-ink-sec">
                            Copy the case note across as-is. The test is whether the next engineer
                            could start without asking you a single question.
                        </p>
                    </div>

                    <!-- Steps -->
                    <div class="card divide-y divide-line overflow-hidden">
                        <div
                            v-for="(step, i) in activeTree.steps"
                            :key="step.id"
                            class="flex items-start gap-3 px-5 py-4 transition-colors"
                            :class="{
                                'bg-negative-bg': stepState(step.id) === 'out',
                                'bg-positive-bg': stepState(step.id) === 'found',
                            }"
                        >
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                :class="
                                    stepState(step.id) === 'found'
                                        ? 'bg-ok text-white'
                                        : stepState(step.id) === 'out'
                                          ? 'bg-surface-alt text-ink-dis'
                                          : 'bg-brand-soft text-brand'
                                "
                            >
                                {{ i + 1 }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-sm leading-relaxed"
                                    :class="
                                        stepState(step.id) === 'out'
                                            ? 'text-ink-dis line-through'
                                            : 'text-ink'
                                    "
                                >
                                    {{ step.prompt }}
                                    <span
                                        class="chip ml-1.5 bg-surface-alt text-ink-sec"
                                        :title="`Layer ${step.layer}`"
                                    >
                                        L{{ step.layer }}
                                    </span>
                                </p>

                                <!-- Revealed only once the step is marked as the
                                     cause, so the tree stays an exercise rather
                                     than a list of answers. -->
                                <p
                                    v-if="step.fix && stepState(step.id) === 'found'"
                                    class="mt-2 rounded-lg bg-surface px-3.5 py-2.5 text-sm leading-relaxed text-ink-sec"
                                >
                                    {{ step.fix }}
                                </p>

                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
                                        :class="
                                            stepState(step.id) === 'out'
                                                ? 'border-negative bg-negative text-white'
                                                : 'border-line bg-surface text-ink-sec hover:border-negative hover:text-negative'
                                        "
                                        @click="markStep(step.id, 'out')"
                                    >
                                        Checked — not this
                                    </button>

                                    <button
                                        v-if="step.fix"
                                        type="button"
                                        class="cursor-pointer rounded-lg border px-3 py-1.5 text-xs font-semibold transition-colors"
                                        :class="
                                            stepState(step.id) === 'found'
                                                ? 'border-ok bg-ok text-white'
                                                : 'border-line bg-surface text-ink-sec hover:border-ok hover:text-ok'
                                        "
                                        @click="markStep(step.id, 'found')"
                                    >
                                        This is the cause
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ═══ REFERENCE ═══════════════════════════════ -->
                <section class="mt-8">
                    <button
                        type="button"
                        class="mb-3 flex w-full cursor-pointer items-center gap-2 text-left"
                        @click="showLayers = !showLayers"
                    >
                        <h2 class="flex-1 text-lg font-bold text-navy">
                            Reference: the layer model &amp; priority
                        </h2>
                        <svg
                            class="h-4 w-4 fill-none stroke-current stroke-2 text-ink-dis transition-transform"
                            :class="showLayers ? '' : '-rotate-90'"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6" />
                        </svg>
                    </button>

                    <div v-show="showLayers">
                        <div class="card mb-4 overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-surface-alt text-left">
                                            <th class="w-10 px-4 py-3 font-semibold text-navy">#</th>
                                            <th class="w-44 px-4 py-3 font-semibold text-navy">Layer</th>
                                            <th class="px-4 py-3 font-semibold text-navy">
                                                First place to look
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-line">
                                        <tr v-for="layer in layers" :key="layer.n">
                                            <td class="px-4 py-3 font-bold text-brand">{{ layer.n }}</td>
                                            <td class="px-4 py-3 font-medium text-navy">
                                                {{ layer.name }}
                                            </td>
                                            <td class="px-4 py-3 leading-relaxed text-ink-sec">
                                                {{ layer.look }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p class="mb-6 text-sm text-ink-dis italic">
                            Affects everything → 1–2. One object → 4–6. One screen → 3. Fine here,
                            missing downstream → 7.
                        </p>

                        <h3 class="mb-1 text-base font-bold text-navy">
                            Priority is impact × urgency
                        </h3>
                        <p class="mb-4 text-sm text-ink-sec">
                            Not who shouts loudest. Tap a cell to set it on the case note.
                        </p>

                        <div class="overflow-x-auto">
                            <div class="grid min-w-[560px] grid-cols-[130px_repeat(3,1fr)] gap-2">
                                <div></div>
                                <div
                                    v-for="(header, i) in [
                                        ['Urgency: high', 'now / blocking'],
                                        ['Urgency: medium', 'today / workaround'],
                                        ['Urgency: low', 'next week'],
                                    ]"
                                    :key="i"
                                    class="px-2 pb-1 text-center"
                                >
                                    <span class="block text-xs font-semibold text-navy">
                                        {{ header[0] }}
                                    </span>
                                    <span class="block text-xs text-ink-dis">{{ header[1] }}</span>
                                </div>

                                <template v-for="row in priority_matrix" :key="row.row">
                                    <div class="flex flex-col justify-center py-2 pr-2">
                                        <span class="text-xs font-semibold text-navy">
                                            {{ row.row }}
                                        </span>
                                        <span class="text-xs leading-snug text-ink-dis">
                                            {{ row.sub }}
                                        </span>
                                    </div>

                                    <button
                                        v-for="(cell, i) in row.cells"
                                        :key="`${row.row}-${i}`"
                                        type="button"
                                        :aria-pressed="
                                            priority?.code === cell.priority &&
                                            priority?.reason.startsWith(row.row)
                                        "
                                        class="cursor-pointer rounded-xl border px-3 py-3 text-center transition-colors"
                                        :class="
                                            priority?.code === cell.priority &&
                                            priority?.reason.startsWith(row.row)
                                                ? 'border-brand bg-brand-soft'
                                                : 'border-line bg-surface hover:border-line-strong hover:bg-surface-alt'
                                        "
                                        @click="selectPriority(cell.priority, row.row, i)"
                                    >
                                        <span
                                            class="block text-base font-extrabold"
                                            :class="priorityColour(cell.priority)"
                                        >
                                            {{ cell.priority }}
                                        </span>
                                        <span class="mt-1 block text-xs leading-snug text-ink-dis">
                                            {{ cell.example }}
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <div class="card mt-6 p-5">
                            <h4 class="mb-2 text-sm font-bold text-navy">The telematics twist</h4>
                            <p class="text-sm leading-relaxed text-ink-sec">
                                Some of this data has a
                                <strong class="text-navy">physical clock</strong> attached.
                                Refrigerated cargo spoils. A stolen vehicle moves. Ask:
                                <strong class="text-navy">
                                    what happens in the real world while this stays broken?
                                </strong>
                                A sensor fault that looks like P4 on a reefer trailer is a P2.
                            </p>
                        </div>
                    </div>
                </section>
            </main>

            <!-- ═══ CASE NOTE ═══════════════════════════════════ -->
            <aside>
                <div class="card overflow-hidden lg:sticky lg:top-24">
                    <div class="flex items-center gap-2 border-b border-line bg-surface-alt px-5 py-3.5">
                        <span class="h-2 w-2 rounded-full bg-brand"></span>
                        <span class="flex-1 text-sm font-bold text-navy">Case note</span>
                        <span
                            v-if="priority"
                            class="chip bg-brand-soft text-brand"
                        >
                            {{ priority.code }}
                        </span>
                    </div>

                    <div class="p-5">
                        <div class="mb-3 flex flex-col gap-2">
                            <input
                                v-model="customer"
                                placeholder="Customer / contract"
                                autocomplete="off"
                                aria-label="Customer or contract"
                                :class="fieldClass"
                            />
                            <input
                                v-model="objectRef"
                                placeholder="Object name / IMEI"
                                autocomplete="off"
                                aria-label="Object name or IMEI"
                                :class="fieldClass"
                            />
                        </div>

                        <pre
                            class="mb-3 max-h-80 overflow-auto rounded-lg border border-line bg-surface-alt p-3.5 font-mono text-xs leading-relaxed whitespace-pre-wrap text-ink-sec"
                            >{{ note }}</pre
                        >

                        <textarea
                            v-model="customerTold"
                            rows="2"
                            placeholder="What the customer has been told"
                            aria-label="What the customer has been told"
                            :class="fieldClass"
                            class="mb-3 resize-y"
                        ></textarea>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="flex-1 cursor-pointer rounded-lg bg-brand py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover"
                                @click="copyNote"
                            >
                                Copy note
                            </button>
                            <button
                                type="button"
                                class="flex-1 cursor-pointer rounded-lg border border-line py-2.5 text-sm font-semibold text-ink-sec transition-colors hover:border-brand hover:text-brand"
                                @click="newCase"
                            >
                                New case
                            </button>
                        </div>

                        <p class="mt-2.5 min-h-4 text-center text-xs text-ink-dis">{{ stamp }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </EmployeeLayout>
</template>

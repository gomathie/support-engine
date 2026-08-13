<script setup>
/**
 * The Support Panel: a diagnostic decision tree on the left, a case note that
 * writes itself on the right.
 *
 * The tri-state per step (untouched / ruled out / cause found), the
 * reveal-on-found fix text, the priority matrix and the clipboard copy with its
 * execCommand fallback are all carried over from the prototype. The case itself
 * is now a persisted row rather than an object that evaporated on reload.
 */
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from '@/lib/debounce';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

const props = defineProps({
    trees: { type: Array, default: () => [] },
    case: { type: Object, default: null },
    priority_matrix: { type: Array, default: () => [] },
});

const activeTab = ref('diagnose');

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

const activeTree = computed(
    () => props.trees.find((tree) => tree.id === selectedTreeId.value) ?? null,
);

const note = computed(() => props.case?.note ?? 'No symptom selected yet.');

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
    if (selectedTreeId.value === tree.id) {
        selectedTreeId.value = null;
    } else {
        selectedTreeId.value = tree.id;
        stepStates.value = {};
    }
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
        // Fallback for browsers and contexts where the async clipboard API is
        // unavailable, notably non-HTTPS origins.
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

const layers = [
    { n: 1, name: 'Access & rights', look: "Failed Login Attempts, Blocked Users, Disabled Users → then the user's rights" },
    { n: 2, name: 'Contract & modules', look: 'Is the module on the contract? A missing button is usually this, not a bug' },
    { n: 3, name: 'Interface & filters', look: 'List filters, group selection, columns, date range' },
    { n: 4, name: 'Object config', look: 'Object card, tariff, device ID/type, temporary blocking' },
    { n: 5, name: 'Sensor config', look: 'Field mapping → conversion formula → calibration table, in that order' },
    { n: 6, name: 'Device & data', look: 'Sensors tracing, satellite count, Connection Lost, Devices offline' },
    { n: 7, name: 'Relay', look: 'Statistics: Queue, Err, Send, Ack' },
];

const tabs = [
    { id: 'diagnose', label: 'Diagnose' },
    { id: 'classify', label: 'Classify' },
];

const fieldClass =
    'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';
</script>

<template>
    <Head title="Support Panel" />

    <EmployeeLayout>
        <div class="mb-6">
            <h1 class="mb-1 text-2xl font-extrabold text-navy">Support panel</h1>
            <p class="text-sm text-ink-sec">
                Work the layers top down. Rule each check out as you go — the case note writes
                itself.
            </p>
        </div>

        <!-- ─── TABS ────────────────────────────────────────── -->
        <div class="mb-6 flex gap-1 border-b border-line" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.id"
                class="-mb-px cursor-pointer border-b-2 px-4 py-2.5 text-sm font-semibold transition-colors"
                :class="
                    activeTab === tab.id
                        ? 'border-brand text-brand'
                        : 'border-transparent text-ink-sec hover:text-ink'
                "
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
            </button>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_340px]">
            <main class="min-w-0">
                <!-- ═══ DIAGNOSE ════════════════════════════════ -->
                <section v-show="activeTab === 'diagnose'">
                    <h2 class="mb-3 text-lg font-bold text-navy">What did the customer say?</h2>

                    <div class="mb-6 flex flex-col gap-2">
                        <button
                            v-for="tree in trees"
                            :key="tree.id"
                            type="button"
                            :aria-pressed="selectedTreeId === tree.id"
                            class="flex w-full cursor-pointer items-center gap-3 rounded-xl border px-4 py-3.5 text-left transition-colors"
                            :class="
                                selectedTreeId === tree.id
                                    ? 'border-brand bg-brand-soft'
                                    : 'border-line bg-surface hover:border-line-strong hover:bg-surface-alt'
                            "
                            @click="selectSymptom(tree)"
                        >
                            <span class="flex-1 text-sm text-ink">“{{ tree.question }}”</span>
                            <span class="chip bg-surface-alt text-ink-sec">
                                Layer {{ tree.layer_label }}
                            </span>
                        </button>
                    </div>

                    <!-- Decision tree -->
                    <div v-if="activeTree" class="card mb-6 divide-y divide-line overflow-hidden">
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
                                    <span class="chip ml-1.5 bg-surface-alt text-ink-sec">
                                        L{{ step.layer }}
                                    </span>
                                </p>

                                <!-- Revealed only once the step is marked as the
                                     cause, exactly as the prototype did. -->
                                <p
                                    v-if="step.fix && stepState(step.id) === 'found'"
                                    class="mt-2 rounded-lg bg-surface px-3 py-2 text-sm leading-relaxed text-ink-sec"
                                >
                                    {{ step.fix }}
                                </p>

                                <div class="mt-2.5 flex gap-2">
                                    <button
                                        type="button"
                                        class="cursor-pointer rounded-lg border px-2.5 py-1 text-xs font-semibold transition-colors"
                                        :class="
                                            stepState(step.id) === 'out'
                                                ? 'border-negative bg-negative text-white'
                                                : 'border-line bg-surface text-ink-sec hover:border-negative hover:text-negative'
                                        "
                                        @click="markStep(step.id, 'out')"
                                    >
                                        Ruled out
                                    </button>

                                    <button
                                        v-if="step.fix"
                                        type="button"
                                        class="cursor-pointer rounded-lg border px-2.5 py-1 text-xs font-semibold transition-colors"
                                        :class="
                                            stepState(step.id) === 'found'
                                                ? 'border-ok bg-ok text-white'
                                                : 'border-line bg-surface text-ink-sec hover:border-ok hover:text-ok'
                                        "
                                        @click="markStep(step.id, 'found')"
                                    >
                                        Cause found
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Layer model reference -->
                    <h2 class="mb-3 text-lg font-bold text-navy">The layer model</h2>

                    <div class="card overflow-hidden">
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

                    <p class="mt-3 text-sm text-ink-dis italic">
                        Affects everything → 1–2. One object → 4–6. One screen → 3. Fine here,
                        missing downstream → 7.
                    </p>
                </section>

                <!-- ═══ CLASSIFY ════════════════════════════════ -->
                <section v-show="activeTab === 'classify'">
                    <h2 class="mb-1 text-lg font-bold text-navy">Priority is impact × urgency</h2>
                    <p class="mb-5 text-sm text-ink-sec">
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
                                    <span class="text-xs font-semibold text-navy">{{ row.row }}</span>
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
                        <h3 class="mb-2 text-base font-bold text-navy">The telematics twist</h3>
                        <p class="text-sm leading-relaxed text-ink-sec">
                            Some of this data has a
                            <strong class="text-navy">physical clock</strong> attached. Refrigerated
                            cargo spoils. A stolen vehicle moves. Ask:
                            <strong class="text-navy">
                                what happens in the real world while this stays broken?
                            </strong>
                            A sensor fault that looks like P4 on a reefer trailer is a P2.
                        </p>
                    </div>
                </section>
            </main>

            <!-- ═══ CASE NOTE ═══════════════════════════════════ -->
            <aside>
                <div class="card overflow-hidden lg:sticky lg:top-24">
                    <div class="flex items-center gap-2 border-b border-line bg-surface-alt px-5 py-3.5">
                        <span class="h-2 w-2 rounded-full bg-brand"></span>
                        <span class="flex-1 text-sm font-bold text-navy">Case note</span>
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
                            class="mb-3 max-h-72 overflow-auto rounded-lg border border-line bg-surface-alt p-3.5 font-mono text-xs leading-relaxed whitespace-pre-wrap text-ink-sec"
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
                                Copy
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

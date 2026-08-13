<script setup>
/**
 * The Support Panel.
 *
 * Layout is the prototype's: symptom list and decision tree on the left, sticky
 * case note on the right, collapsing to one column under 900px. The tri-state
 * per step (untouched / ruled out / cause found), the reveal-on-found fix text,
 * the priority matrix and the clipboard copy with its execCommand fallback are
 * all preserved.
 *
 * The case itself is now a row. The prototype wrote `S` to an API that did not
 * exist, so every case evaporated on reload; here each change is persisted and
 * the note is rebuilt server-side by BuildCaseNote.
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

const activeTree = computed(() =>
    props.trees.find((tree) => tree.id === selectedTreeId.value) ?? null,
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
            onSuccess: () => {
                stamp.value = 'saved ' + new Date().toLocaleTimeString();
            },
            onError: () => {
                stamp.value = 'not saved';
            },
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
        stamp.value = 'copied to clipboard';
    } catch {
        // The prototype's fallback, kept for browsers and contexts where the
        // async clipboard API is unavailable (notably non-HTTPS origins).
        const ta = document.createElement('textarea');
        ta.value = note.value;
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            stamp.value = 'copied to clipboard';
        } catch {
            stamp.value = 'select the text above to copy';
        }
        ta.remove();
    }
}

const priorityColour = (code) =>
    ({
        P1: 'text-negative',
        P2: 'text-warning-dim dark:text-warning',
        P3: 'text-[#D9C86A]',
        P4: 'text-primary',
        P5: 'text-ink-dis',
    })[code] ?? 'text-ink';

const tabs = [
    { id: 'diagnose', label: 'Diagnose' },
    { id: 'classify', label: 'Classify' },
];
</script>

<template>
    <Head title="Support Panel" />

    <EmployeeLayout>
        <!-- ─── SUB BAR ─────────────────────────────────────── -->
        <div class="sticky top-[54px] z-20 border-b border-line bg-nav backdrop-blur-[6px]">
            <div class="mx-auto flex max-w-[1180px] flex-wrap items-center gap-4.5 px-4.5 py-3">
                <div class="mono-label text-[11px] tracking-[2.5px] whitespace-nowrap text-primary">
                    PILOT <b class="tracking-[1px] text-ink">SUPPORT PANEL</b>
                </div>

                <div class="flex flex-1 flex-wrap gap-1" role="tablist">
                    <button
                        v-for="tab in tabs"
                        :key="tab.id"
                        type="button"
                        role="tab"
                        :aria-selected="activeTab === tab.id"
                        class="mono-label cursor-pointer rounded border px-3 py-1.5 text-[11px] tracking-[1.5px] transition-all duration-250"
                        :class="
                            activeTab === tab.id
                                ? 'border-warning bg-warning font-bold text-on-warning'
                                : 'border-line bg-transparent text-ink-sec hover:text-ink'
                        "
                        @click="activeTab = tab.id"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
        </div>

        <div
            class="mx-auto grid max-w-[1180px] grid-cols-1 items-start gap-5.5 px-4.5 py-5.5 lg:grid-cols-[1fr_340px]"
        >
            <main>
                <!-- ═══ DIAGNOSE ════════════════════════════════ -->
                <section v-show="activeTab === 'diagnose'">
                    <p class="mono-label mb-1 text-[10px] tracking-[2px] text-ink-dis">Module B</p>
                    <h1 class="mb-1.5 text-[19px] font-bold">Work the layers, top down</h1>
                    <p class="mb-4.5 max-w-[62ch] text-[13px] leading-relaxed text-ink-sec">
                        Pick what the customer said. Rule each check out as you go — the case note
                        on the right writes itself. Faults live near the top of this list far more
                        often than the bottom, so resist jumping to the device.
                    </p>

                    <!-- Symptom picker -->
                    <div class="mb-5 flex flex-col gap-1.75">
                        <button
                            v-for="tree in trees"
                            :key="tree.id"
                            type="button"
                            :aria-pressed="selectedTreeId === tree.id"
                            class="flex w-full cursor-pointer items-center gap-2.75 rounded-[7px] border px-3.25 py-2.75 text-left transition-all duration-250"
                            :class="
                                selectedTreeId === tree.id
                                    ? 'border-warning bg-surface-alt'
                                    : 'border-line bg-surface hover:border-ink-dis'
                            "
                            @click="selectSymptom(tree)"
                        >
                            <span class="flex-1 text-[13.5px] text-ink">“{{ tree.question }}”</span>
                            <span class="font-mono text-[10px] whitespace-nowrap text-ink-dis">
                                layer {{ tree.layer_label }}
                            </span>
                        </button>
                    </div>

                    <!-- Decision tree -->
                    <div
                        v-if="activeTree"
                        class="mb-3.5 overflow-hidden rounded-lg border border-line bg-surface"
                    >
                        <div
                            v-for="(step, i) in activeTree.steps"
                            :key="step.id"
                            class="flex items-start gap-2.75 border-b border-line px-3.25 py-2.75 last:border-b-0"
                            :class="{
                                'bg-negative-bg': stepState(step.id) === 'out',
                                'bg-positive-bg': stepState(step.id) === 'found',
                            }"
                        >
                            <div
                                class="min-w-4 pt-0.5 font-mono text-[11px] font-bold"
                                :class="
                                    stepState(step.id) === 'out'
                                        ? 'text-ink-dis'
                                        : 'text-warning-dim dark:text-warning'
                                "
                            >
                                {{ i + 1 }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div
                                    class="text-[13px] leading-relaxed"
                                    :class="
                                        stepState(step.id) === 'out'
                                            ? 'text-ink-dis line-through'
                                            : 'text-ink'
                                    "
                                >
                                    {{ step.prompt }}
                                    <span
                                        class="mono-label ml-1.5 inline-block rounded-[3px] border border-line px-1.25 py-px align-[1px] text-[9px] tracking-[1px] text-ink-dis"
                                    >
                                        L{{ step.layer }}
                                    </span>
                                </div>

                                <!-- Revealed only once the step is marked as the
                                     cause, exactly as the prototype did. -->
                                <div
                                    v-if="step.fix && stepState(step.id) === 'found'"
                                    class="mt-1.75 text-xs leading-relaxed text-primary"
                                >
                                    {{ step.fix }}
                                </div>

                                <div class="flex gap-1.25 pt-1.5">
                                    <button
                                        type="button"
                                        class="mono-label cursor-pointer rounded border px-1.75 py-1 text-[10px] leading-none whitespace-nowrap transition-all duration-250"
                                        :class="
                                            stepState(step.id) === 'out'
                                                ? 'border-negative bg-negative text-white'
                                                : 'border-line bg-transparent text-ink-dis hover:border-ink-dis hover:text-ink'
                                        "
                                        @click="markStep(step.id, 'out')"
                                    >
                                        Ruled out
                                    </button>

                                    <button
                                        v-if="step.fix"
                                        type="button"
                                        class="mono-label cursor-pointer rounded border px-1.75 py-1 text-[10px] leading-none whitespace-nowrap transition-all duration-250"
                                        :class="
                                            stepState(step.id) === 'found'
                                                ? 'border-positive bg-positive text-white dark:text-[#020617]'
                                                : 'border-line bg-transparent text-ink-dis hover:border-ink-dis hover:text-ink'
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
                    <h2 class="mt-6.5 mb-2.5 text-sm font-bold">The layer model</h2>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-[12.5px]">
                            <thead>
                                <tr>
                                    <th class="w-9.5 border border-line bg-surface-alt px-2.5 py-2 text-left text-[11px]">#</th>
                                    <th class="w-37.5 border border-line bg-surface-alt px-2.5 py-2 text-left text-[11px]">Layer</th>
                                    <th class="border border-line bg-surface-alt px-2.5 py-2 text-left text-[11px]">First place to look</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="layer in [
                                    { n: 1, name: 'Access &amp; rights', look: 'Failed Login Attempts, Blocked Users, Disabled Users → then the user\'s rights' },
                                    { n: 2, name: 'Contract &amp; modules', look: 'Is the module on the contract? A missing button is usually this, not a bug' },
                                    { n: 3, name: 'Interface &amp; filters', look: 'List filters, group selection, columns, date range' },
                                    { n: 4, name: 'Object config', look: 'Object card, tariff, device ID/type, temporary blocking' },
                                    { n: 5, name: 'Sensor config', look: 'Field mapping → conversion formula → calibration table, in that order' },
                                    { n: 6, name: 'Device &amp; data', look: 'Sensors tracing, satellite count, Connection Lost, Devices offline' },
                                    { n: 7, name: 'Relay', look: 'Statistics: Queue, Err, Send, Ack' },
                                ]" :key="layer.n">
                                    <td class="border border-line px-2.5 py-2 font-mono text-[11px] whitespace-nowrap text-warning-dim dark:text-warning">
                                        {{ layer.n }}
                                    </td>
                                    <td class="border border-line px-2.5 py-2 align-top">
                                        <b class="text-ink" v-html="layer.name"></b>
                                    </td>
                                    <td class="border border-line px-2.5 py-2 align-top leading-relaxed text-ink-sec">
                                        {{ layer.look }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-2 mb-4.5 text-xs text-ink-dis italic">
                        Affects everything → 1–2. One object → 4–6. One screen → 3. Fine here,
                        missing downstream → 7.
                    </p>
                </section>

                <!-- ═══ CLASSIFY ════════════════════════════════ -->
                <section v-show="activeTab === 'classify'">
                    <p class="mono-label mb-1 text-[10px] tracking-[2px] text-ink-dis">Module D</p>
                    <h1 class="mb-1.5 text-[19px] font-bold">Classify before you queue</h1>
                    <p class="mb-4.5 max-w-[62ch] text-[13px] leading-relaxed text-ink-sec">
                        Priority is impact × urgency — not who shouts loudest. Tap a cell to set it
                        on the case note.
                    </p>

                    <div class="overflow-x-auto">
                        <div class="grid min-w-[520px] grid-cols-[auto_repeat(3,1fr)] gap-1.25">
                            <div></div>
                            <div
                                v-for="(header, i) in [
                                    'Urgency: high<br>now / blocking',
                                    'Urgency: medium<br>today / workaround',
                                    'Urgency: low<br>next week',
                                ]"
                                :key="i"
                                class="mono-label flex items-center justify-center p-1.25 text-center text-[10px] tracking-[1px] text-ink-dis"
                                v-html="header"
                            ></div>

                            <template v-for="row in priority_matrix" :key="row.row">
                                <div
                                    class="mono-label flex max-w-[118px] items-center p-1.25 text-[10px] leading-snug tracking-[1px] text-ink-dis"
                                >
                                    <span>
                                        <b class="block text-ink">{{ row.row }}</b>
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
                                    class="cursor-pointer rounded-md border px-1.75 py-2.25 text-center transition-all duration-250"
                                    :class="
                                        priority?.code === cell.priority &&
                                        priority?.reason.startsWith(row.row)
                                            ? 'border-warning bg-surface-alt'
                                            : 'border-line bg-surface hover:border-ink-dis'
                                    "
                                    @click="selectPriority(cell.priority, row.row, i)"
                                >
                                    <div
                                        class="font-mono text-sm font-bold"
                                        :class="priorityColour(cell.priority)"
                                    >
                                        {{ cell.priority }}
                                    </div>
                                    <div class="mt-0.75 text-[10.5px] leading-snug text-ink-dis">
                                        {{ cell.example }}
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-line bg-surface px-4 py-3.5">
                        <h3 class="mb-1.75 text-[13px] font-bold">The telematics twist</h3>
                        <p class="text-[12.5px] leading-relaxed text-ink-sec">
                            Some of this data has a <b class="text-ink">physical clock</b> attached.
                            Refrigerated cargo spoils. A stolen vehicle moves. Ask:
                            <b class="text-ink">what happens in the real world while this stays
                            broken?</b> A sensor fault that looks like P4 on a reefer trailer is a P2.
                        </p>
                    </div>
                </section>
            </main>

            <!-- ═══ CASE NOTE ═══════════════════════════════════ -->
            <aside>
                <div
                    class="overflow-hidden rounded-lg border border-line bg-surface lg:sticky lg:top-[120px]"
                >
                    <div class="flex items-center gap-2 border-b border-line bg-surface-alt px-3.25 py-2.25">
                        <span class="h-1.5 w-1.5 rounded-full bg-primary"></span>
                        <span class="mono-label flex-1 text-[10px] tracking-[2px] text-ink-sec">
                            Case note
                        </span>
                    </div>

                    <div class="px-3.25 py-3">
                        <div class="mb-2.75 flex flex-col gap-1.75">
                            <input
                                v-model="customer"
                                placeholder="Customer / contract"
                                autocomplete="off"
                                aria-label="Customer or contract"
                                class="w-full rounded-[5px] border border-line bg-canvas px-2.25 py-1.75 font-mono text-[11.5px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none"
                            />
                            <input
                                v-model="objectRef"
                                placeholder="Object name / IMEI"
                                autocomplete="off"
                                aria-label="Object name or IMEI"
                                class="w-full rounded-[5px] border border-line bg-canvas px-2.25 py-1.75 font-mono text-[11.5px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none"
                            />
                        </div>

                        <pre
                            class="mb-2.5 max-h-[290px] overflow-auto rounded-[5px] border border-line bg-canvas p-2.5 font-mono text-[11px] leading-relaxed whitespace-pre-wrap text-ink-sec"
                            >{{ note }}</pre
                        >

                        <textarea
                            v-model="customerTold"
                            rows="2"
                            placeholder="What the customer has been told"
                            aria-label="What the customer has been told"
                            class="mb-2.5 w-full resize-y rounded-[5px] border border-line bg-canvas px-2.25 py-1.75 font-mono text-[11px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none"
                        ></textarea>

                        <div class="flex gap-1.5">
                            <button
                                type="button"
                                class="mono-label flex-1 cursor-pointer rounded-[5px] border border-primary bg-primary py-1.75 text-[10.5px] font-bold tracking-[1px] text-on-accent"
                                @click="copyNote"
                            >
                                Copy
                            </button>
                            <button
                                type="button"
                                class="mono-label flex-1 cursor-pointer rounded-[5px] border border-line bg-transparent py-1.75 text-[10.5px] tracking-[1px] text-ink-sec transition-colors hover:border-ink-dis hover:text-ink"
                                @click="newCase"
                            >
                                New case
                            </button>
                        </div>

                        <div class="mt-1.75 min-h-3 text-center font-mono text-[9.5px] text-ink-dis">
                            {{ stamp }}
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </EmployeeLayout>
</template>

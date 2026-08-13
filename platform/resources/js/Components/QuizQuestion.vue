<script setup>
/**
 * One question on the taking-a-quiz screen.
 *
 * The options it receives carry an id and a label and nothing else — the server
 * strips `is_correct` before the payload is built, so there is no correct
 * answer present in the page to find.
 */
const props = defineProps({
    question: { type: Object, required: true },
    index: { type: Number, required: true },
    modelValue: { type: Object, required: true },
});

const emit = defineEmits(['update:modelValue']);

function toggleOption(optionId) {
    const current = props.modelValue.option_ids ?? [];

    const next = props.question.multiple
        ? current.includes(optionId)
            ? current.filter((id) => id !== optionId)
            : [...current, optionId]
        : [optionId];

    emit('update:modelValue', { ...props.modelValue, option_ids: next });
}

function updateText(event) {
    emit('update:modelValue', { ...props.modelValue, text: event.target.value });
}

const isSelected = (optionId) => (props.modelValue.option_ids ?? []).includes(optionId);
</script>

<template>
    <div class="rounded-lg border border-line bg-surface px-5 py-4">
        <div class="mb-3 flex items-start gap-3">
            <span class="mt-0.5 min-w-4 font-mono text-[11px] font-bold text-warning-dim dark:text-warning">
                {{ index + 1 }}
            </span>

            <div class="flex-1">
                <p class="text-[13px] leading-relaxed text-ink">{{ question.prompt }}</p>

                <p class="mono-label mt-1 text-[9px] tracking-[1px] text-ink-dis">
                    {{ question.points }} {{ question.points === 1 ? 'point' : 'points' }}
                    <span v-if="question.multiple"> · select all that apply</span>
                </p>
            </div>
        </div>

        <!-- Choice questions -->
        <div v-if="question.options.length" class="flex flex-col gap-1.5 pl-7">
            <button
                v-for="option in question.options"
                :key="option.id"
                type="button"
                class="flex cursor-pointer items-start gap-2.5 rounded-md border px-3 py-2.5 text-left transition-colors"
                :class="
                    isSelected(option.id)
                        ? 'border-primary bg-primary-bg'
                        : 'border-line bg-transparent hover:border-ink-dis'
                "
                :aria-pressed="isSelected(option.id)"
                @click="toggleOption(option.id)"
            >
                <span
                    class="mt-px h-4 w-4 shrink-0 border-[1.5px] transition-colors"
                    :class="[
                        question.multiple ? 'rounded-[3px]' : 'rounded-full',
                        isSelected(option.id) ? 'border-primary bg-primary' : 'border-ink-dis',
                    ]"
                >
                    <span
                        v-if="isSelected(option.id) && question.multiple"
                        class="relative -top-px left-[3px] block h-2 w-1 rotate-45 border-r-2 border-b-2 border-surface"
                    ></span>
                </span>

                <span class="text-[13px] leading-snug text-ink">{{ option.label }}</span>
            </button>
        </div>

        <!-- Short answer -->
        <div v-else class="pl-7">
            <input
                type="text"
                :value="modelValue.text ?? ''"
                maxlength="1000"
                placeholder="Type your answer"
                class="w-full rounded-[5px] border border-line bg-canvas px-3 py-2 font-mono text-[12px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none"
                @input="updateText"
            />
        </div>
    </div>
</template>

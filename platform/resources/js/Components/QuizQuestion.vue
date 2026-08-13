<script setup>
/**
 * One question on the taking-a-quiz screen.
 *
 * The options it receives carry an id and a label and nothing else — the server
 * strips `is_correct` before building the payload, so there is no answer key
 * present in the page to find.
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
    <div class="card p-6">
        <div class="mb-4 flex items-start gap-3">
            <span
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-soft text-xs font-bold text-brand"
            >
                {{ index + 1 }}
            </span>

            <div class="flex-1">
                <p class="text-base leading-relaxed font-medium text-navy">
                    {{ question.prompt }}
                </p>

                <p class="mt-1 text-xs text-ink-dis">
                    {{ question.points }} {{ question.points === 1 ? 'point' : 'points' }}
                    <span v-if="question.multiple"> · select all that apply</span>
                </p>
            </div>
        </div>

        <!-- Choice questions -->
        <div v-if="question.options.length" class="flex flex-col gap-2 pl-9">
            <button
                v-for="option in question.options"
                :key="option.id"
                type="button"
                class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 text-left transition-colors"
                :class="
                    isSelected(option.id)
                        ? 'border-brand bg-brand-soft'
                        : 'border-line hover:border-line-strong hover:bg-surface-alt'
                "
                :aria-pressed="isSelected(option.id)"
                @click="toggleOption(option.id)"
            >
                <span
                    class="mt-0.5 flex h-[18px] w-[18px] shrink-0 items-center justify-center border-2 transition-colors"
                    :class="[
                        question.multiple ? 'rounded-md' : 'rounded-full',
                        isSelected(option.id) ? 'border-brand bg-brand' : 'border-line-strong',
                    ]"
                >
                    <svg
                        v-if="isSelected(option.id)"
                        class="h-3 w-3 fill-none stroke-white stroke-[3]"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                </span>

                <span class="text-sm leading-snug text-ink">{{ option.label }}</span>
            </button>
        </div>

        <!-- Short answer -->
        <div v-else class="pl-9">
            <input
                type="text"
                :value="modelValue.text ?? ''"
                maxlength="1000"
                placeholder="Type your answer"
                class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none"
                @input="updateText"
            />
        </div>
    </div>
</template>

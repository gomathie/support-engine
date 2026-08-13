<script setup>
/**
 * Public certificate check. No layout, no navigation, no login — somebody
 * outside the company follows this link and needs one answer.
 *
 * Shows only what makes the certificate checkable. No score, no email address,
 * no employee number.
 */
import { Head } from '@inertiajs/vue3';

defineProps({
    certificate: { type: Object, default: null },
    valid: { type: Boolean, default: false },
});
</script>

<template>
    <Head title="Verify certificate" />

    <div class="flex min-h-screen items-center justify-center bg-canvas px-6 py-12">
        <div class="w-full max-w-[480px]">
            <div class="mb-8 flex flex-col items-center text-center">
                <img
                    src="/images/logo.png"
                    alt="PILOT"
                    class="mb-4 h-8 w-auto brightness-0 invert-[0.2] dark:brightness-100 dark:invert-0"
                />
                <span class="mono-label text-[11px] tracking-[2.5px] text-primary">
                    PILOT <b class="tracking-[1px] text-ink">Training Hub</b>
                </span>
            </div>

            <div
                class="rounded-2xl border p-8 text-center"
                :class="valid ? 'border-positive bg-surface' : 'border-negative bg-surface'"
            >
                <template v-if="valid">
                    <div class="mb-4 text-4xl">✓</div>

                    <p class="mono-label mb-4 text-[10px] tracking-[2px] text-positive">
                        Valid certificate
                    </p>

                    <h1 class="mb-1 text-lg font-bold text-ink">
                        {{ certificate.recipient_name }}
                    </h1>

                    <p class="mb-6 text-[13px] text-ink-sec">
                        successfully completed
                        <span class="font-bold text-ink">{{ certificate.course_title }}</span>
                    </p>

                    <dl class="mono-label flex flex-col gap-1.5 text-[10px] tracking-[1px] text-ink-dis">
                        <div>
                            <dt class="inline">Certificate</dt>
                            <dd class="ml-2 inline text-ink-sec">{{ certificate.number }}</dd>
                        </div>
                        <div>
                            <dt class="inline">Completed</dt>
                            <dd class="ml-2 inline text-ink-sec">{{ certificate.completed_at }}</dd>
                        </div>
                        <div>
                            <dt class="inline">Issued</dt>
                            <dd class="ml-2 inline text-ink-sec">{{ certificate.issued_at }}</dd>
                        </div>
                    </dl>
                </template>

                <template v-else>
                    <div class="mb-4 text-4xl">✕</div>

                    <p class="mono-label mb-3 text-[10px] tracking-[2px] text-negative">
                        Not found
                    </p>

                    <p class="text-[13px] leading-relaxed text-ink-sec">
                        No certificate matches this verification link. Check the link is complete,
                        or ask the holder for a current one.
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Public certificate check. No layout, no navigation, no login — somebody
 * outside the company follows this link and needs one answer.
 *
 * Shows only what makes a certificate checkable: who, what, when. No score, no
 * email address, no employee number.
 */
import { Head } from '@inertiajs/vue3';

defineProps({
    certificate: { type: Object, default: null },
    valid: { type: Boolean, default: false },
});
</script>

<template>
    <Head title="Verify certificate" />

    <div class="flex min-h-screen items-center justify-center bg-canvas px-5 py-12">
        <div class="w-full max-w-lg">
            <div class="mb-8 flex items-center justify-center gap-2.5">
                <span
                    class="brand-gradient flex h-9 w-9 items-center justify-center rounded-lg text-lg font-extrabold text-white"
                >
                    P
                </span>
                <span class="text-lg font-extrabold text-navy">
                    Pilot <span class="text-brand">Academy</span>
                </span>
            </div>

            <div class="card p-9 text-center" :class="valid ? 'border-ok/40' : 'border-negative/40'">
                <template v-if="valid">
                    <div
                        class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-ok text-2xl text-white"
                    >
                        ✓
                    </div>

                    <p class="mb-5 text-xs font-semibold tracking-wide text-ok uppercase">
                        Valid certificate
                    </p>

                    <h1 class="mb-1 text-xl font-extrabold text-navy">
                        {{ certificate.recipient_name }}
                    </h1>

                    <p class="mb-7 text-sm text-ink-sec">
                        successfully completed
                        <span class="font-bold text-navy">{{ certificate.course_title }}</span>
                    </p>

                    <dl class="flex flex-col gap-2 border-t border-line pt-5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-ink-dis">Certificate</dt>
                            <dd class="font-medium text-ink">{{ certificate.number }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-dis">Completed</dt>
                            <dd class="font-medium text-ink">{{ certificate.completed_at }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-ink-dis">Issued</dt>
                            <dd class="font-medium text-ink">{{ certificate.issued_at }}</dd>
                        </div>
                    </dl>
                </template>

                <template v-else>
                    <div
                        class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-negative text-2xl text-white"
                    >
                        ✕
                    </div>

                    <p class="mb-3 text-xs font-semibold tracking-wide text-negative uppercase">
                        Not found
                    </p>

                    <p class="text-sm leading-relaxed text-ink-sec">
                        No certificate matches this verification link. Check the link is complete,
                        or ask the holder for a current one.
                    </p>
                </template>
            </div>

            <p class="mt-6 text-center text-sm text-ink-dis">
                Pilot Academy · internal support training
            </p>
        </div>
    </div>
</template>

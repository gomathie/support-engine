<script setup>
import { Head, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import EmptyState from '@/Components/EmptyState.vue';

defineProps({
    certificates: { type: Array, default: () => [] },
});

const formatDate = (iso) =>
    iso
        ? new Date(iso).toLocaleDateString(undefined, {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : '—';
</script>

<template>
    <Head title="Certificates" />

    <EmployeeLayout>
        <div class="mb-6">
            <h1 class="mb-1 text-2xl font-extrabold text-navy">Your certificates</h1>
            <p class="text-sm text-ink-sec">
                Issued automatically when you finish a course. Each one carries a verification link
                anyone can check.
            </p>
        </div>

        <div v-if="certificates.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div
                v-for="certificate in certificates"
                :key="certificate.id"
                class="card overflow-hidden"
            >
                <div class="brand-gradient flex items-center justify-between px-6 py-5">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold tracking-wide text-white/70 uppercase">
                            Certificate
                        </p>
                        <p class="truncate text-sm font-bold text-white">
                            {{ certificate.number }}
                        </p>
                    </div>
                    <span class="text-2xl">🎓</span>
                </div>

                <div class="p-6">
                    <h2 class="mb-1 text-base leading-snug font-bold text-navy">
                        {{ certificate.course_title }}
                    </h2>

                    <p class="mb-5 text-sm text-ink-sec">
                        Awarded to {{ certificate.recipient_name }}
                    </p>

                    <dl class="mb-5 flex flex-wrap gap-x-6 gap-y-2 text-sm">
                        <div>
                            <dt class="text-xs text-ink-dis">Completed</dt>
                            <dd class="font-medium text-ink">
                                {{ formatDate(certificate.completed_at) }}
                            </dd>
                        </div>
                        <div v-if="certificate.score !== null">
                            <dt class="text-xs text-ink-dis">Score</dt>
                            <dd class="font-medium text-ok">
                                {{ Math.round(certificate.score) }}%
                            </dd>
                        </div>
                    </dl>

                    <div class="flex flex-wrap gap-2">
                        <a
                            v-if="certificate.is_ready"
                            :href="certificate.download_url"
                            class="rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white no-underline transition-colors hover:bg-brand-hover"
                        >
                            Download PDF
                        </a>

                        <!-- The record exists the moment the course completes;
                             the file arrives when the queued render finishes. -->
                        <span
                            v-else
                            class="rounded-lg border border-line px-4 py-2 text-sm text-ink-dis"
                        >
                            Generating…
                        </span>

                        <a
                            :href="certificate.verification_url"
                            target="_blank"
                            rel="noopener"
                            class="rounded-lg border border-line px-4 py-2 text-sm font-medium text-ink-sec no-underline transition-colors hover:border-brand hover:text-brand"
                        >
                            Verification link
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <EmptyState
            v-else
            icon="🎓"
            title="No certificates yet"
            description="Finish a course — every lesson plus its final assessment — and a certificate is issued automatically."
        >
            <Link
                :href="route('courses.index')"
                class="inline-block rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white no-underline hover:bg-brand-hover"
            >
                View your courses
            </Link>
        </EmptyState>
    </EmployeeLayout>
</template>

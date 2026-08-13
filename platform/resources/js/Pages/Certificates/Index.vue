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
        <div class="mx-auto max-w-[880px] px-5 py-10">
            <p class="mono-label mb-1.5 text-[11px] tracking-[3px] text-primary">Record</p>
            <h1 class="mb-8 text-xl font-bold">Your certificates</h1>

            <div v-if="certificates.length" class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div
                    v-for="certificate in certificates"
                    :key="certificate.id"
                    class="relative overflow-hidden rounded-2xl border border-line bg-surface p-6"
                >
                    <div
                        class="pointer-events-none absolute -top-16 -right-16 h-40 w-40 rounded-full bg-[radial-gradient(circle,rgba(234,179,8,.12)_0%,transparent_70%)]"
                    ></div>

                    <div class="relative">
                        <div class="mono-label mb-2 text-[9px] tracking-[2px] text-ink-dis">
                            {{ certificate.number }}
                        </div>

                        <h2 class="mb-1 text-base leading-tight font-bold text-ink">
                            {{ certificate.course_title }}
                        </h2>

                        <p class="mb-4 text-xs text-ink-sec">
                            Awarded to {{ certificate.recipient_name }}
                        </p>

                        <dl class="mono-label mb-5 flex flex-wrap gap-4 text-[9px] tracking-[1px] text-ink-dis">
                            <div>
                                <dt class="inline">Completed</dt>
                                <dd class="inline text-ink-sec">
                                    {{ formatDate(certificate.completed_at) }}
                                </dd>
                            </div>
                            <div v-if="certificate.score !== null">
                                <dt class="inline">Score</dt>
                                <dd class="inline text-ink-sec">
                                    {{ Math.round(certificate.score) }}%
                                </dd>
                            </div>
                        </dl>

                        <div class="flex flex-wrap gap-2">
                            <a
                                v-if="certificate.is_ready"
                                :href="certificate.download_url"
                                class="mono-label rounded-[5px] border border-primary bg-primary px-3.5 py-2 text-[10px] font-bold tracking-[1.5px] text-on-accent no-underline"
                            >
                                Download PDF
                            </a>

                            <!-- The record exists the moment the course is
                                 completed; the file arrives when the queued
                                 render finishes. -->
                            <span
                                v-else
                                class="mono-label rounded-[5px] border border-line px-3.5 py-2 text-[10px] tracking-[1.5px] text-ink-dis"
                            >
                                Generating…
                            </span>

                            <a
                                :href="certificate.verification_url"
                                target="_blank"
                                rel="noopener"
                                class="mono-label rounded-[5px] border border-line px-3.5 py-2 text-[10px] tracking-[1.5px] text-ink-sec no-underline transition-colors hover:border-primary hover:text-primary"
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
                description="Complete a course — every lesson plus its final assessment — and a certificate is issued automatically."
            >
                <Link
                    :href="route('courses.index')"
                    class="mono-label rounded-[5px] border border-primary bg-primary px-4 py-2 text-[11px] tracking-[1.5px] text-on-accent no-underline"
                >
                    View your courses
                </Link>
            </EmptyState>
        </div>
    </EmployeeLayout>
</template>

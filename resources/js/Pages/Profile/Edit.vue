<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

const props = defineProps({
    profile: { type: Object, required: true },
});

const details = useForm({
    name: props.profile.name,
    email: props.profile.email,
    certificate_name: props.profile.certificate_name ?? '',
});

const password = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const field =
    'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

const label = 'mb-1.5 block text-sm font-medium text-navy';

const button =
    'cursor-pointer rounded-lg bg-brand px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-hover disabled:opacity-60';
</script>

<template>
    <Head title="Profile" />

    <EmployeeLayout>
        <div class="mx-auto max-w-2xl">
            <h1 class="mb-6 text-2xl font-extrabold text-navy">Your profile</h1>

            <!-- ─── EMPLOYMENT (read only) ──────────────────── -->
            <div class="card mb-6 bg-surface-alt p-6">
                <p class="mb-3 text-xs font-semibold tracking-wide text-ink-dis uppercase">
                    Set by your administrator
                </p>
                <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-3">
                    <div>
                        <dt class="text-ink-dis">Department</dt>
                        <dd class="font-medium text-ink">{{ profile.department || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-dis">Job title</dt>
                        <dd class="font-medium text-ink">{{ profile.job_title || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-dis">Employee number</dt>
                        <dd class="font-medium text-ink">{{ profile.employee_number || '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- ─── DETAILS ─────────────────────────────────── -->
            <form
                class="card mb-6 p-6"
                @submit.prevent="details.put(route('profile.update'), { preserveScroll: true })"
            >
                <h2 class="mb-5 text-lg font-bold text-navy">Details</h2>

                <div class="mb-4">
                    <label for="name" :class="label">Name</label>
                    <input id="name" v-model="details.name" type="text" :class="field" />
                    <p v-if="details.errors.name" class="mt-1 text-sm text-negative">
                        {{ details.errors.name }}
                    </p>
                </div>

                <div class="mb-4">
                    <label for="email" :class="label">Email</label>
                    <input id="email" v-model="details.email" type="email" :class="field" />
                    <p v-if="details.errors.email" class="mt-1 text-sm text-negative">
                        {{ details.errors.email }}
                    </p>
                </div>

                <div class="mb-6">
                    <label for="certificate_name" :class="label">Name on certificates</label>
                    <input
                        id="certificate_name"
                        v-model="details.certificate_name"
                        type="text"
                        :placeholder="details.name"
                        :class="field"
                    />
                    <p class="mt-1.5 text-sm text-ink-dis">
                        Leave blank to use your name above. Set this if your certificates should
                        carry your full legal name.
                    </p>
                </div>

                <button type="submit" :disabled="details.processing" :class="button">Save</button>
            </form>

            <!-- ─── PASSWORD ────────────────────────────────── -->
            <form
                class="card p-6"
                @submit.prevent="
                    password.put(route('profile.password'), {
                        preserveScroll: true,
                        onSuccess: () => password.reset(),
                    })
                "
            >
                <h2 class="mb-5 text-lg font-bold text-navy">Password</h2>

                <div class="mb-4">
                    <label for="current_password" :class="label">Current password</label>
                    <input
                        id="current_password"
                        v-model="password.current_password"
                        type="password"
                        autocomplete="current-password"
                        :class="field"
                    />
                    <p v-if="password.errors.current_password" class="mt-1 text-sm text-negative">
                        {{ password.errors.current_password }}
                    </p>
                </div>

                <div class="mb-4">
                    <label for="new_password" :class="label">New password</label>
                    <input
                        id="new_password"
                        v-model="password.password"
                        type="password"
                        autocomplete="new-password"
                        :class="field"
                    />
                    <p v-if="password.errors.password" class="mt-1 text-sm text-negative">
                        {{ password.errors.password }}
                    </p>
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" :class="label">Confirm</label>
                    <input
                        id="password_confirmation"
                        v-model="password.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :class="field"
                    />
                </div>

                <button type="submit" :disabled="password.processing" :class="button">
                    Update password
                </button>
            </form>
        </div>
    </EmployeeLayout>
</template>

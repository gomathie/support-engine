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

const fieldClass =
    'w-full rounded-[5px] border border-line bg-canvas px-3 py-2.5 font-mono text-[12px] text-ink placeholder:text-ink-dis focus:border-primary focus:outline-none';

const labelClass = 'mono-label mb-1.5 block text-[10px] tracking-[1.5px] text-ink-sec';
</script>

<template>
    <Head title="Profile" />

    <EmployeeLayout>
        <div class="mx-auto max-w-[560px] px-5 py-10">
            <p class="mono-label mb-1.5 text-[11px] tracking-[3px] text-primary">Account</p>
            <h1 class="mb-8 text-xl font-bold">Your profile</h1>

            <!-- ─── EMPLOYMENT DETAILS (read only) ──────────── -->
            <div class="mb-8 rounded-lg border border-line bg-surface-alt px-4 py-3.5">
                <p class="mono-label mb-2.5 text-[9px] tracking-[2px] text-ink-dis">
                    Set by your administrator
                </p>
                <dl class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <dt class="text-ink-dis">Department</dt>
                        <dd class="text-ink">{{ profile.department || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-dis">Job title</dt>
                        <dd class="text-ink">{{ profile.job_title || '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-ink-dis">Employee number</dt>
                        <dd class="font-mono text-ink">{{ profile.employee_number || '—' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- ─── DETAILS ─────────────────────────────────── -->
            <form
                class="mb-8 rounded-lg border border-line bg-surface p-6"
                @submit.prevent="details.put(route('profile.update'), { preserveScroll: true })"
            >
                <h2 class="mb-4 text-sm font-bold">Details</h2>

                <div class="mb-4">
                    <label for="name" :class="labelClass">Name</label>
                    <input id="name" v-model="details.name" type="text" :class="fieldClass" />
                    <p v-if="details.errors.name" class="mt-1 text-xs text-negative">
                        {{ details.errors.name }}
                    </p>
                </div>

                <div class="mb-4">
                    <label for="email" :class="labelClass">Email</label>
                    <input id="email" v-model="details.email" type="email" :class="fieldClass" />
                    <p v-if="details.errors.email" class="mt-1 text-xs text-negative">
                        {{ details.errors.email }}
                    </p>
                </div>

                <div class="mb-5">
                    <label for="certificate_name" :class="labelClass">Name on certificates</label>
                    <input
                        id="certificate_name"
                        v-model="details.certificate_name"
                        type="text"
                        :placeholder="details.name"
                        :class="fieldClass"
                    />
                    <p class="mt-1.5 text-[11px] text-ink-dis">
                        Leave blank to use your name above. Set this if your certificates should
                        carry your full legal name.
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="details.processing"
                    class="mono-label cursor-pointer rounded-[5px] border border-primary bg-primary px-4 py-2.5 text-[11px] font-bold tracking-[1.5px] text-on-accent disabled:opacity-60"
                >
                    Save
                </button>
            </form>

            <!-- ─── PASSWORD ────────────────────────────────── -->
            <form
                class="rounded-lg border border-line bg-surface p-6"
                @submit.prevent="
                    password.put(route('profile.password'), {
                        preserveScroll: true,
                        onSuccess: () => password.reset(),
                    })
                "
            >
                <h2 class="mb-4 text-sm font-bold">Password</h2>

                <div class="mb-4">
                    <label for="current_password" :class="labelClass">Current password</label>
                    <input
                        id="current_password"
                        v-model="password.current_password"
                        type="password"
                        autocomplete="current-password"
                        :class="fieldClass"
                    />
                    <p v-if="password.errors.current_password" class="mt-1 text-xs text-negative">
                        {{ password.errors.current_password }}
                    </p>
                </div>

                <div class="mb-4">
                    <label for="new_password" :class="labelClass">New password</label>
                    <input
                        id="new_password"
                        v-model="password.password"
                        type="password"
                        autocomplete="new-password"
                        :class="fieldClass"
                    />
                    <p v-if="password.errors.password" class="mt-1 text-xs text-negative">
                        {{ password.errors.password }}
                    </p>
                </div>

                <div class="mb-5">
                    <label for="password_confirmation" :class="labelClass">Confirm</label>
                    <input
                        id="password_confirmation"
                        v-model="password.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        :class="fieldClass"
                    />
                </div>

                <button
                    type="submit"
                    :disabled="password.processing"
                    class="mono-label cursor-pointer rounded-[5px] border border-primary bg-primary px-4 py-2.5 text-[11px] font-bold tracking-[1.5px] text-on-accent disabled:opacity-60"
                >
                    Update password
                </button>
            </form>
        </div>
    </EmployeeLayout>
</template>

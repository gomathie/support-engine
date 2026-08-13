<script setup>
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const field =
    'w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm text-ink placeholder:text-ink-dis focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <div class="flex min-h-screen flex-col bg-canvas">
        <div class="flex flex-1 items-center justify-center px-5 py-12">
            <div class="w-full max-w-md">
                <div class="mb-8 flex items-center justify-center gap-2.5">
                    <span
                        class="brand-gradient flex h-10 w-10 items-center justify-center rounded-xl text-xl font-extrabold text-white"
                    >
                        P
                    </span>
                    <span class="text-xl leading-tight font-extrabold text-navy">
                        Pilot <span class="text-brand">Academy</span>
                        <span class="block text-[10px] font-semibold tracking-wide text-ink-dis uppercase">
                            Support Team
                        </span>
                    </span>
                </div>

                <div class="card p-8">
                    <h1 class="mb-1 text-xl font-extrabold text-navy">Sign in</h1>
                    <p class="mb-6 text-sm text-ink-sec">
                        Use the account your team lead set up for you.
                    </p>

                    <div
                        v-if="status"
                        class="mb-5 rounded-xl border border-ok/40 bg-positive-bg px-4 py-3 text-sm text-ok"
                    >
                        {{ status }}
                    </div>

                    <form class="flex flex-col gap-4" @submit.prevent="submit">
                        <div>
                            <label for="email" class="mb-1.5 block text-sm font-medium text-navy">
                                Email
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                name="email"
                                autocomplete="username"
                                required
                                autofocus
                                :class="field"
                                placeholder="you@company.com"
                            />
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-medium text-navy">
                                Password
                            </label>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                :class="field"
                                placeholder="••••••••"
                            />
                        </div>

                        <!-- One message covers a bad address and a bad password
                             alike, so the form cannot be used to enumerate staff. -->
                        <p v-if="form.errors.email" class="text-sm text-negative">
                            {{ form.errors.email }}
                        </p>

                        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-sec">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-line accent-[var(--color-brand)]"
                            />
                            Keep me signed in
                        </label>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="mt-2 w-full cursor-pointer rounded-lg bg-brand py-3 text-sm font-semibold text-white transition-colors hover:bg-brand-hover disabled:opacity-60"
                        >
                            {{ form.processing ? 'Signing in…' : 'Sign in' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <footer class="px-5 py-8 text-center text-sm text-ink-dis">
            Pilot Academy · internal support training
        </footer>
    </div>
</template>

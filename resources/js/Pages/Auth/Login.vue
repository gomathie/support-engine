<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    status: { type: String, default: null },
});

const showPassword = ref(false);

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
                <div class="mb-8 flex flex-col items-center gap-3 text-center">
                    <img src="/images/logo.png" alt="PILOT" class="h-10 w-auto dark:hidden" />
                    <img
                        src="/images/logo-white.png"
                        alt="PILOT"
                        class="hidden h-10 w-auto dark:block"
                    />
                    <span class="text-lg font-bold text-navy">Support Training Hub</span>
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
                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    name="password"
                                    autocomplete="current-password"
                                    required
                                    :class="[field, 'pr-10']"
                                    placeholder="••••••••"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-dis hover:text-ink transition-colors"
                                    @click="showPassword = !showPassword"
                                >
                                    <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" />
                                    </svg>
                                </button>
                            </div>
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
            Support Training Hub · PILOT platform onboarding
        </footer>
    </div>
</template>

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

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-canvas px-6 py-12">
        <!-- The hero glows from the prototype's landing page, reused so the
             first screen an employee sees already looks like the product. -->
        <div
            class="pointer-events-none absolute -top-30 left-1/2 h-[800px] w-[800px] -translate-x-1/2 bg-[radial-gradient(circle,rgba(2,132,199,.1)_0%,transparent_65%)]"
        ></div>
        <div
            class="pointer-events-none absolute top-5 left-[40%] h-[600px] w-[600px] -translate-x-1/2 bg-[radial-gradient(circle,rgba(234,179,8,.08)_0%,transparent_60%)]"
        ></div>

        <div class="relative z-1 w-full max-w-[400px]">
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

            <div class="rounded-2xl border border-line bg-surface p-8">
                <h1 class="mb-1 text-xl font-bold text-ink">Sign in</h1>
                <p class="mb-6 text-[13px] text-ink-sec">
                    Use the account your administrator set up for you.
                </p>

                <div
                    v-if="status"
                    class="mb-4 rounded-md border border-positive bg-positive-bg px-3 py-2 text-xs text-positive"
                >
                    {{ status }}
                </div>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div>
                        <label
                            for="email"
                            class="mono-label mb-1.5 block text-[10px] tracking-[1.5px] text-ink-sec"
                        >
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
                            class="w-full rounded-[5px] border border-line bg-canvas px-3 py-2.5 font-mono text-[12px] text-ink transition-colors placeholder:text-ink-dis focus:border-primary focus:outline-none"
                            placeholder="you@company.com"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mono-label mb-1.5 block text-[10px] tracking-[1.5px] text-ink-sec"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            required
                            class="w-full rounded-[5px] border border-line bg-canvas px-3 py-2.5 font-mono text-[12px] text-ink transition-colors placeholder:text-ink-dis focus:border-primary focus:outline-none"
                            placeholder="••••••••"
                        />
                    </div>

                    <!-- One message covers a bad address and a bad password
                         alike, so the form cannot be used to enumerate staff. -->
                    <p v-if="form.errors.email" class="text-xs text-negative">
                        {{ form.errors.email }}
                    </p>

                    <label class="flex cursor-pointer items-center gap-2 text-xs text-ink-sec">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-3.5 w-3.5 rounded-[3px] border-line accent-[var(--color-primary)]"
                        />
                        Keep me signed in
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mono-label mt-2 w-full cursor-pointer rounded-[5px] border border-primary bg-primary py-2.5 text-[11px] font-bold tracking-[1.5px] text-on-accent transition-colors hover:bg-primary-hover disabled:opacity-60"
                    >
                        {{ form.processing ? 'Signing in…' : 'Sign in' }}
                    </button>
                </form>
            </div>

            <p class="mono-label mt-6 text-center text-[10px] tracking-[1px] text-ink-dis">
                1st-Line Support Onboarding
            </p>
        </div>
    </div>
</template>

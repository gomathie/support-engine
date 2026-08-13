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
    'w-full rounded-lg border border-white/20 bg-white/10 px-3.5 py-2.5 text-sm text-white placeholder:text-white/50 focus:border-white/60 focus:ring-2 focus:ring-white/20 focus:outline-none backdrop-blur-sm transition-all';

function submit() {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
}
</script>

<template>
    <Head title="Sign in" />

    <!-- Full-bleed illustrated background -->
    <div class="login-page">
        <!-- Hero background image -->
        <div class="login-bg" />

        <!-- Animated dark gradient overlay -->
        <div class="login-overlay" />

        <!-- Floating animated orbs for depth -->
        <div class="orb orb-1" />
        <div class="orb orb-2" />
        <div class="orb orb-3" />

        <!-- Page content -->
        <div class="login-content">
            <!-- Logo + wordmark -->
            <div class="mb-8 flex flex-col items-center gap-3 text-center">
                <img src="/images/logo-white.png" alt="PILOT" class="h-11 w-auto drop-shadow-lg" />
                <span class="text-lg font-bold tracking-wide text-white/90">Support Training Hub</span>
                <p class="text-xs text-white/50 tracking-widest uppercase">Powered by PILOT</p>
            </div>

            <!-- Frosted-glass login card -->
            <div class="glass-card">
                <h1 class="mb-1 text-xl font-extrabold text-white">Welcome back</h1>
                <p class="mb-6 text-sm text-white/60">
                    Sign in with the account your team lead set up for you.
                </p>

                <div
                    v-if="status"
                    class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-300"
                >
                    {{ status }}
                </div>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-white/80">
                            Email address
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
                        <label for="password" class="mb-1.5 block text-sm font-medium text-white/80">
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
                    <p v-if="form.errors.email" class="text-sm text-red-300">
                        {{ form.errors.email }}
                    </p>

                    <label class="flex cursor-pointer items-center gap-2 text-sm text-white/60">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-white/30 accent-blue-400"
                        />
                        Keep me signed in
                    </label>

                    <button
                        id="login-submit"
                        type="submit"
                        :disabled="form.processing"
                        class="login-btn"
                    >
                        <span v-if="!form.processing">Sign in →</span>
                        <span v-else class="flex items-center justify-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                            </svg>
                            Signing in…
                        </span>
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-white/30">
                Support Training Hub · PILOT platform onboarding
            </p>
        </div>
    </div>
</template>

<style scoped>
/* ─── Page shell ─────────────────────────────────────────────── */
.login-page {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

/* ─── Hero background ─────────────────────────────────────────── */
.login-bg {
    position: absolute;
    inset: 0;
    background-image: url('/images/login-bg.jpg');
    background-size: cover;
    background-position: center;
    animation: bgDrift 30s ease-in-out infinite alternate;
}

@keyframes bgDrift {
    from { transform: scale(1) translate(0, 0); }
    to   { transform: scale(1.06) translate(-1%, 1%); }
}

/* ─── Overlay ──────────────────────────────────────────────────── */
.login-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(10, 37, 64, 0.72) 0%,
        rgba(20, 99, 255, 0.38) 60%,
        rgba(10, 37, 64, 0.80) 100%
    );
}

/* ─── Floating orbs ────────────────────────────────────────────── */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: 0.35;
    pointer-events: none;
}

.orb-1 {
    width: 420px; height: 420px;
    background: #1463ff;
    top: -100px; left: -120px;
    animation: orbFloat1 18s ease-in-out infinite;
}

.orb-2 {
    width: 320px; height: 320px;
    background: #06b6d4;
    bottom: -80px; right: -80px;
    animation: orbFloat2 22s ease-in-out infinite;
}

.orb-3 {
    width: 200px; height: 200px;
    background: #7c3aed;
    top: 50%; right: 12%;
    animation: orbFloat3 16s ease-in-out infinite;
}

@keyframes orbFloat1 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(40px, 30px) scale(1.08); }
}
@keyframes orbFloat2 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(-30px, -20px) scale(1.05); }
}
@keyframes orbFloat3 {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50%       { transform: translate(20px, -40px) scale(1.1); }
}

/* ─── Content container ────────────────────────────────────────── */
.login-content {
    position: relative;
    z-index: 10;
    width: 100%;
    max-width: 420px;
    padding: 1.25rem;
}

/* ─── Frosted-glass card ───────────────────────────────────────── */
.glass-card {
    background: rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 1.25rem;
    padding: 2.25rem;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.35),
        0 0 0 1px rgba(255, 255, 255, 0.06) inset;
}

/* ─── Sign-in button ───────────────────────────────────────────── */
.login-btn {
    margin-top: 0.5rem;
    width: 100%;
    cursor: pointer;
    border-radius: 0.625rem;
    background: linear-gradient(135deg, #1463ff 0%, #0f4fd8 100%);
    padding: 0.8rem 1rem;
    font-size: 0.9rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.02em;
    border: none;
    box-shadow: 0 4px 20px rgba(20, 99, 255, 0.45);
    transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
}

.login-btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 8px 28px rgba(20, 99, 255, 0.55);
}

.login-btn:active:not(:disabled) {
    transform: translateY(0);
}

.login-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

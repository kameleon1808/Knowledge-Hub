<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                <Link href="/" class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400 text-slate-950 font-semibold"
                    >
                        KH
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Knowledge Hub</p>
                        <p class="text-lg font-semibold">za Timove</p>
                    </div>
                </Link>
                <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Secure Access</span>
            </div>
        </header>

        <main class="flex min-h-[calc(100vh-96px)] items-center justify-center px-6 py-12">
            <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900/70 p-8 shadow-xl">
                <slot />
            </div>
        </main>
    </div>
</template>

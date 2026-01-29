<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Home" />

    <section class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-teal-500/40 bg-teal-500/10 px-4 py-1 text-xs uppercase tracking-[0.2em] text-teal-200">
                Foundation Phase
            </div>
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                Knowledge Hub za Timove
                <span class="block text-slate-400">internal Q&amp;A, learning, and shared expertise.</span>
            </h1>
            <p class="max-w-2xl text-base text-slate-300">
                Phase A focuses on the technical foundation: a reliable Laravel 12 backend, Inertia-powered
                Vue 3 UI, and a Dockerized stack with PostgreSQL + pgvector. Business features land in the
                next phases.
            </p>
            <div class="flex flex-wrap gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 px-5 py-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <p class="mt-2 text-lg font-semibold">Ready for Phase B</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 px-5 py-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Stack</p>
                    <p class="mt-2 text-lg font-semibold">Laravel / Inertia / Vue 3</p>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 px-5 py-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Database</p>
                    <p class="mt-2 text-lg font-semibold">PostgreSQL + pgvector</p>
                </div>
            </div>
        </div>

        <aside class="rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/60 to-teal-900/30 p-8">
            <h2 class="text-lg font-semibold">What ships in Phase A</h2>
            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Dockerized PHP-FPM, Nginx, Node, PostgreSQL with pgvector.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Inertia + Vue 3 UI pipeline, Vite HMR, Tailwind styling.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Quality tooling: Pint, Larastan, ESLint, Prettier, smoke tests.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Baseline roles + pivot migrations (no policies or features yet).
                </li>
            </ul>
            <div class="mt-6 rounded-2xl border border-slate-700/60 bg-slate-900/60 px-5 py-4 text-sm text-slate-400">
                Next: authentication, teams, Q&amp;A, and AI assistants.
            </div>
        </aside>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <Head title="Home" />

    <section class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
            <div class="inline-flex items-center gap-2 rounded-full border border-teal-500/40 bg-teal-500/10 px-4 py-1 text-xs uppercase tracking-[0.2em] text-teal-200">
                Phases A–I complete
            </div>
            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                Knowledge Hub for Teams
                <span class="block text-slate-400">internal Q&amp;A, learning, and shared expertise.</span>
            </h1>
            <p class="max-w-2xl text-base text-slate-300">
                A full-featured platform: Q&amp;A with voting and reputation, categories and tags, comments and bookmarks,
                real-time updates, optional AI-generated answers, and an AI knowledge base (RAG) over project documents.
            </p>
            <div class="flex flex-wrap gap-3">
                <Link
                    v-if="!user"
                    :href="route('login')"
                    class="rounded-full border border-teal-400 bg-teal-400 px-5 py-2 text-sm font-semibold text-slate-950 transition hover:bg-teal-300"
                >
                    Log in
                </Link>
                <Link
                    v-if="!user"
                    :href="route('register')"
                    class="rounded-full border border-slate-700 bg-slate-900/70 px-5 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500"
                >
                    Register
                </Link>
                <Link
                    v-if="user"
                    :href="route('dashboard')"
                    class="rounded-full border border-slate-700 bg-slate-900/70 px-5 py-2 text-sm font-semibold text-slate-200 transition hover:border-slate-500"
                >
                    Go to dashboard
                </Link>
            </div>
            <div class="flex flex-wrap gap-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/60 px-5 py-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</p>
                    <p class="mt-2 text-lg font-semibold">All phases complete</p>
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
            <h2 class="text-lg font-semibold">What's included</h2>
            <ul class="mt-4 space-y-3 text-sm text-slate-300">
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Auth &amp; RBAC: Admin, Moderator, Member; admin panel and user management.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Q&amp;A core: questions, answers, Markdown, images; voting, accepted answer, reputation.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Categories, tags, filters, and full-text search.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Comments, bookmarks, and in-app notifications.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Real-time updates (answers, votes, notifications) via Laravel Reverb.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    Optional AI: generate draft answers; provider-agnostic, fully audited.
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-1 h-2 w-2 rounded-full bg-teal-400"></span>
                    AI Knowledge Base: projects, document/email ingestion, RAG, export (Markdown/PDF), activity log.
                </li>
            </ul>
            <div class="mt-6 rounded-2xl border border-slate-700/60 bg-slate-900/60 px-5 py-4 text-sm text-slate-400">
                Log in to ask questions, browse by category and tags, join projects, or use the AI knowledge base.
            </div>
        </aside>
    </section>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
            <div class="flex flex-col gap-4">
                <div class="inline-flex items-center gap-2 rounded-full border border-teal-500/40 bg-teal-500/10 px-4 py-1 text-xs uppercase tracking-[0.2em] text-teal-200">
                    Member Area
                </div>
                <h1 class="text-3xl font-semibold">You’re signed in.</h1>
                <p class="max-w-2xl text-sm text-slate-300">
                    You have access to questions, projects, bookmarks, and the AI knowledge base.
                </p>
                <div class="mt-4">
                    <Link
                        :href="route('projects.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-teal-500/50 bg-teal-500/10 px-5 py-2.5 text-sm font-medium text-teal-200 transition hover:border-teal-400 hover:bg-teal-500/20"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />
                        </svg>
                        Projects
                    </Link>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

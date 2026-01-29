<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AdminLayout,
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <div class="space-y-6">
        <div>
            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Admin Dashboard</p>
            <h1 class="mt-2 text-3xl font-semibold">Operations Overview</h1>
            <p class="mt-3 text-sm text-slate-300">
                This panel provides access to user management today and expands in later phases.
            </p>
        </div>
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Users</p>
                <p class="mt-2 text-lg font-semibold text-slate-100">Manage roles</p>
                <p class="mt-2 text-sm text-slate-400">Assign Admin, Moderator, or Član.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Categories</p>
                <p class="mt-2 text-lg font-semibold text-slate-100">Phase E</p>
                <p class="mt-2 text-sm text-slate-400">Structure ready for future CRUD.</p>
            </div>
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tags</p>
                <p class="mt-2 text-lg font-semibold text-slate-100">Phase E</p>
                <p class="mt-2 text-sm text-slate-400">Waiting on content workflows.</p>
            </div>
        </div>
    </div>
</template>

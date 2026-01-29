<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
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
                    Phase B delivers authentication, role-based access control, and the admin skeleton.
                    Content workflows will arrive in later phases.
                </p>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

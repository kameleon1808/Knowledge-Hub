<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AuthenticatedLayout,
});
</script>

<template>
    <Head title="Moderator Area" />

    <div class="space-y-4 rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Moderator</p>
        <h1 class="text-3xl font-semibold">Moderator Toolkit</h1>
        <p class="text-sm text-slate-300">
            Moderators can edit or remove any content. The moderation tools will be added in later phases.
        </p>
    </div>
</template>

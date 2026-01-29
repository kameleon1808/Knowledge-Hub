<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AdminLayout,
});
</script>

<template>
    <Head title="Tags" />

    <div class="space-y-4">
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tags</p>
        <h1 class="text-2xl font-semibold">Coming in Phase E</h1>
        <p class="text-sm text-slate-300">
            Tag management arrives in Phase E. This placeholder keeps navigation intact without
            exposing unfinished CRUD.
        </p>
    </div>
</template>

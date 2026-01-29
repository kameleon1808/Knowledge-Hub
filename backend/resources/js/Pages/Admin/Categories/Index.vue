<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({
    layout: AdminLayout,
});
</script>

<template>
    <Head title="Categories" />

    <div class="space-y-4">
        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Categories</p>
        <h1 class="text-2xl font-semibold">Coming in Phase E</h1>
        <p class="text-sm text-slate-300">
            Category management is planned for Phase E. This section will host creation, editing,
            and moderation workflows.
        </p>
    </div>
</template>

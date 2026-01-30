<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    projects: {
        type: Object,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const formatDate = (value) =>
    value ? new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(new Date(value)) : '';
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Knowledge Base</p>
                    <h1 class="mt-2 text-3xl font-semibold">Projects</h1>
                    <p class="mt-2 text-sm text-slate-400">
                        Projects group documents and emails for search and AI answers.
                    </p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('projects.create')"
                    class="inline-flex items-center justify-center rounded-xl border border-teal-400 bg-teal-400/10 px-4 py-2 text-sm font-medium text-teal-300 transition hover:bg-teal-400/20"
                >
                    New Project
                </Link>
            </div>

            <div v-if="flashSuccess" class="rounded-xl border border-teal-800/70 bg-teal-900/30 px-4 py-3 text-sm text-teal-200">
                {{ flashSuccess }}
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-800">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-950/70 text-left text-xs uppercase tracking-[0.2em] text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Owner</th>
                            <th class="px-4 py-3">Updated</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800 bg-slate-900/60">
                        <tr v-for="project in projects.data" :key="project.id">
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('projects.show', project.id)"
                                    class="font-medium text-teal-300 hover:text-teal-200"
                                >
                                    {{ project.name }}
                                </Link>
                                <p v-if="project.description" class="mt-1 max-w-md truncate text-xs text-slate-500">
                                    {{ project.description }}
                                </p>
                            </td>
                            <td class="px-4 py-3 text-slate-300">
                                {{ project.owner?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ formatDate(project.updated_at) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('projects.show', project.id)"
                                    class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest text-slate-300 hover:border-slate-500"
                                >
                                    Open
                                </Link>
                                <Link
                                    :href="route('projects.edit', project.id)"
                                    class="ml-2 rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest text-slate-300 hover:border-slate-500"
                                >
                                    Edit
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="projects.data.length === 0">
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                No projects yet. Create one to get started.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="projects.data.length > 0 && (projects.prev_page_url || projects.next_page_url)" class="flex justify-center gap-2">
                <Link
                    v-if="projects.prev_page_url"
                    :href="projects.prev_page_url"
                    class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                >
                    Previous
                </Link>
                <Link
                    v-if="projects.next_page_url"
                    :href="projects.next_page_url"
                    class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                >
                    Next
                </Link>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

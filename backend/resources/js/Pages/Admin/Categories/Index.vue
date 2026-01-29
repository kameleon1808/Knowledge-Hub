<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

defineOptions({
    layout: AdminLayout,
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const confirmDelete = (id) => {
    if (!confirm('Delete this category?')) return;
    router.delete(route('admin.categories.destroy', id));
};
</script>

<template>
    <Head title="Categories" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Content Taxonomy</p>
                <h1 class="mt-1 text-3xl font-semibold">Categories</h1>
                <p class="mt-2 text-sm text-slate-300">
                    Organize questions into a clear hierarchy. Parent-first deletion is enforced.
                </p>
            </div>
            <Link
                :href="route('admin.categories.create')"
                class="inline-flex items-center rounded-full bg-teal-400 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-teal-300"
            >
                New Category
            </Link>
        </div>

        <div v-if="flashSuccess" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            {{ flashSuccess }}
        </div>
        <div v-if="flashError" class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
            {{ flashError }}
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/70 text-left text-xs uppercase tracking-[0.2em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Parent</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Description</th>
                        <th class="px-4 py-3">Children</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900/60">
                    <tr v-for="category in categories" :key="category.id">
                        <td class="px-4 py-3 font-medium text-slate-100">
                            {{ category.name }}
                        </td>
                        <td class="px-4 py-3 text-slate-300">
                            {{ category.parent?.name || '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ category.slug }}</td>
                        <td class="px-4 py-3 text-slate-300">
                            {{ category.description || '—' }}
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ category.children_count }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <Link
                                :href="route('admin.categories.edit', category.id)"
                                class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest text-slate-300 hover:border-teal-400 hover:text-teal-200"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="rounded-full border border-rose-500/50 px-3 py-1 text-xs uppercase tracking-widest text-rose-200 hover:border-rose-400"
                                @click="confirmDelete(category.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!categories.length">
                        <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-400">
                            No categories yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

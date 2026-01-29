<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    tags: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search || '');
const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const submit = () => {
    router.get(route('admin.tags.index'), { search: search.value || undefined }, { preserveState: true, replace: true });
};

const clearSearch = () => {
    search.value = '';
    submit();
};

const confirmDelete = (id) => {
    if (!confirm('Delete this tag? It will be detached from questions.')) return;
    router.delete(route('admin.tags.destroy', id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Tags" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Taxonomy</p>
                <h1 class="mt-1 text-3xl font-semibold">Tags</h1>
                <p class="mt-2 text-sm text-slate-300">Manage reusable labels for questions. Slugs are auto-generated.</p>
            </div>
            <Link
                :href="route('admin.tags.create')"
                class="inline-flex items-center rounded-full bg-teal-400 px-4 py-2 text-sm font-semibold text-slate-900 transition hover:bg-teal-300"
            >
                New Tag
            </Link>
        </div>

        <div v-if="flashSuccess" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
            {{ flashSuccess }}
        </div>

        <form @submit.prevent="submit" class="flex flex-wrap gap-2">
            <input
                v-model="search"
                type="text"
                name="search"
                placeholder="Search name or slug"
                class="w-full max-w-sm rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
            />
            <button
                type="submit"
                class="rounded-xl border border-slate-700 bg-slate-900/70 px-4 text-xs font-semibold uppercase tracking-widest text-slate-200 hover:border-slate-500"
            >
                Search
            </button>
            <button
                v-if="search"
                type="button"
                @click="clearSearch"
                class="rounded-xl border border-transparent bg-slate-800/70 px-3 text-xs uppercase tracking-widest text-slate-300 hover:bg-slate-700/70"
            >
                Clear
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-800">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/70 text-left text-xs uppercase tracking-[0.2em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900/60">
                    <tr v-for="tag in tags.data" :key="tag.id">
                        <td class="px-4 py-3 font-medium text-slate-100">{{ tag.name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ tag.slug }}</td>
                        <td class="px-4 py-3 text-slate-400">
                            {{ new Date(tag.created_at).toLocaleDateString() }}
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <Link
                                :href="route('admin.tags.edit', tag.id)"
                                class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest text-slate-300 hover:border-teal-400 hover:text-teal-200"
                            >
                                Edit
                            </Link>
                            <button
                                type="button"
                                class="rounded-full border border-rose-500/50 px-3 py-1 text-xs uppercase tracking-widest text-rose-200 hover:border-rose-400"
                                @click="confirmDelete(tag.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                    <tr v-if="tags.data.length === 0">
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-400">
                            No tags found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="tags.links.length > 3" class="flex flex-wrap gap-2">
            <template v-for="link in tags.links" :key="link.label">
                <span
                    v-if="!link.url"
                    class="cursor-not-allowed rounded-full border border-slate-800 px-3 py-1 text-xs uppercase tracking-widest text-slate-600"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest"
                    :class="link.active ? 'border-teal-400 bg-teal-400 text-slate-950' : 'text-slate-300 hover:border-slate-500'"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>

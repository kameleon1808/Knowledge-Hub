<script setup>
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    roleLabels: {
        type: Object,
        default: () => ({}),
    },
});

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search || '');

const submit = () => {
    router.get(
        route('admin.users.index'),
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
};

const clearSearch = () => {
    search.value = '';
    submit();
};

const formatDate = (value) =>
    new Intl.DateTimeFormat('en-US', { dateStyle: 'medium' }).format(new Date(value));
</script>

<template>
    <Head title="Users" />

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">User Management</p>
                <h1 class="mt-2 text-3xl font-semibold">Users</h1>
                <p class="mt-2 text-sm text-slate-300">
                    Update access roles for the team. Changes take effect immediately.
                </p>
            </div>
            <form @submit.prevent="submit" class="flex w-full max-w-sm gap-2">
                <input
                    v-model="search"
                    type="text"
                    name="search"
                    placeholder="Search name or email"
                    class="w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
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
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800">
            <table class="min-w-full divide-y divide-slate-800 text-sm">
                <thead class="bg-slate-950/70 text-left text-xs uppercase tracking-[0.2em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3">Created</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800 bg-slate-900/60">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-4 py-3 font-medium text-slate-100">
                            {{ user.name }}
                        </td>
                        <td class="px-4 py-3 text-slate-300">
                            {{ user.email }}
                        </td>
                        <td class="px-4 py-3 text-slate-300">
                            {{ roleLabels[user.role] || user.role }}
                        </td>
                        <td class="px-4 py-3 text-slate-400">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <Link
                                :href="route('admin.users.edit', user.id)"
                                class="rounded-full border border-slate-700 px-3 py-1 text-xs uppercase tracking-widest text-slate-300 hover:border-slate-500"
                            >
                                Edit
                            </Link>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.links.length > 3" class="flex flex-wrap gap-2">
            <template v-for="link in users.links" :key="link.label">
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

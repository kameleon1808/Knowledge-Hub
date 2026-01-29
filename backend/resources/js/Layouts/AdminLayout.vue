<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const currentUrl = computed(() => page.url);

const navLinkClass = (href, exact = false) => {
    const isActive = exact ? currentUrl.value === href : currentUrl.value.startsWith(href);

    return isActive
        ? 'flex items-center justify-between rounded-2xl border border-teal-500/40 bg-teal-500/10 px-4 py-3 text-sm text-teal-200'
        : 'flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-900/70 px-4 py-3 text-sm text-slate-300 hover:border-slate-700 hover:text-slate-100';
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="grid gap-6 lg:grid-cols-[240px_1fr]">
            <aside class="space-y-3">
                <div class="rounded-3xl border border-slate-800 bg-slate-900/70 p-4">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Admin</p>
                    <p class="mt-2 text-lg font-semibold text-slate-100">Control Center</p>
                </div>
                <nav class="space-y-3">
                    <Link :href="route('admin.dashboard')" :class="navLinkClass('/admin', true)">
                        <span>Dashboard</span>
                    </Link>
                    <Link :href="route('admin.users.index')" :class="navLinkClass('/admin/users')">
                        <span>Users</span>
                    </Link>
                    <Link :href="route('admin.categories.index')" :class="navLinkClass('/admin/categories')">
                        <span>Categories</span>
                        <span class="text-xs text-slate-500">Phase E</span>
                    </Link>
                    <Link :href="route('admin.tags.index')" :class="navLinkClass('/admin/tags')">
                        <span>Tags</span>
                        <span class="text-xs text-slate-500">Phase E</span>
                    </Link>
                </nav>
            </aside>

            <section class="rounded-3xl border border-slate-800 bg-slate-900/70 p-8">
                <slot />
            </section>
        </div>
    </AuthenticatedLayout>
</template>

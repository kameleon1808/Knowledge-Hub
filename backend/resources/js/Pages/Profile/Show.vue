<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const roleLabels = {
    admin: 'Admin',
    moderator: 'Moderator',
    member: 'Član',
};
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Profile</p>
                <h1 class="mt-2 text-3xl font-semibold">{{ user.name }}</h1>
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-6">
                <div class="grid gap-4 text-sm text-slate-300">
                    <div class="flex items-center justify-between border-b border-slate-800/60 pb-4">
                        <span class="text-slate-500">Email</span>
                        <span>{{ user.email }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-800/60 pb-4">
                        <span class="text-slate-500">Role</span>
                        <span>{{ roleLabels[user.role] || 'Member' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Reputation</span>
                        <span class="text-lg font-semibold text-teal-200">{{ user.reputation }}</span>
                    </div>
                </div>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

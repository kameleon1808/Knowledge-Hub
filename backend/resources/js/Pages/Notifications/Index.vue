<script setup>
import { computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    notifications: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const markAsRead = async (id) => {
    await window.axios.post(route('notifications.read', id));
    router.reload({ only: ['notifications'], preserveScroll: true });
};

const markAllAsRead = async () => {
    await window.axios.post(route('notifications.readAll'));
    router.reload({ only: ['notifications'], preserveScroll: true });
};

const notificationLink = (notification) => {
    const questionId = notification.data?.question_id;
    if (!questionId) return '#';
    return route('questions.show', questionId);
};
</script>

<template>
    <Head title="Notifications" />
    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex items-center justify-between rounded-3xl border border-slate-800 bg-slate-900/70 px-6 py-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-teal-200">Phase F</p>
                    <h1 class="text-2xl font-semibold">Notifications</h1>
                    <p class="text-sm text-slate-400">Database notifications for activity on your questions.</p>
                </div>
                <button
                    type="button"
                    class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:border-teal-400 hover:text-teal-100"
                    @click="markAllAsRead"
                >
                    Mark all as read
                </button>
            </div>

            <div class="grid gap-4">
                <div
                    v-for="notification in notifications.data"
                    :key="notification.id"
                    class="rounded-2xl border p-4 transition"
                    :class="notification.read_at ? 'border-slate-800 bg-slate-950/70' : 'border-teal-400/40 bg-teal-400/5'
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                {{ notification.read_at ? 'Read' : 'Unread' }} · {{ new Date(notification.created_at).toLocaleString() }}
                            </p>
                            <Link :href="notificationLink(notification)" class="mt-1 block text-lg font-semibold text-teal-200 hover:text-teal-100">
                                {{ notification.data?.question_title || 'Question' }}
                            </Link>
                            <p class="mt-2 text-sm text-slate-200">New answer posted by user #{{ notification.data?.actor_user_id }}</p>
                            <p class="mt-1 text-sm text-slate-400" v-if="notification.data?.snippet">
                                {{ notification.data.snippet }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <button
                                v-if="!notification.read_at"
                                type="button"
                                class="rounded-full border border-teal-400 px-3 py-1 text-xs uppercase tracking-[0.2em] text-teal-200 hover:border-teal-300"
                                @click="markAsRead(notification.id)"
                            >
                                Mark read
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="!notifications.data.length" class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-sm text-slate-400">
                    No notifications yet.
                </div>
            </div>

            <div v-if="notifications.links?.length" class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="link in notifications.links"
                    :key="link.label"
                    :href="link.url || ''"
                    class="rounded-full border border-slate-800 px-3 py-1 text-sm"
                    :class="[
                        link.active ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:text-slate-100',
                        !link.url && 'cursor-not-allowed opacity-50',
                    ]"
                    v-html="link.label"
                />
            </div>
        </section>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { getEcho } from '@/lib/echo.js';

const page = usePage();
const user = computed(() => page.props.auth.user);
const unreadCountFromServer = computed(() => page.props.notifications?.unread_count ?? 0);
const unreadNotifications = ref(unreadCountFromServer.value);

watch(unreadCountFromServer, (val) => {
    unreadNotifications.value = val;
});

const channelName = computed(() =>
    user.value?.id ? `user.${user.value.id}.notifications` : null
);

let echoChannel = null;

onMounted(() => {
    unreadNotifications.value = unreadCountFromServer.value;
    const echo = user.value?.id ? getEcho() : null;
    if (!echo || !channelName.value) return;
    echoChannel = echo.private(channelName.value);
    echoChannel.listen('.NotificationCreated', (payload) => {
        unreadNotifications.value = payload.unread_count ?? unreadNotifications.value + 1;
    });
});

onUnmounted(() => {
    if (channelName.value && echoChannel) {
        getEcho()?.leave(channelName.value);
    }
});

const roleLabels = {
    admin: 'Admin',
    moderator: 'Moderator',
    member: 'Član',
};

const isAdmin = computed(() => user.value?.role === 'admin');
const isModerator = computed(() => user.value?.role === 'moderator');

const linkClass = (active) =>
    active
        ? 'text-teal-200'
        : 'text-slate-400 hover:text-slate-100';
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/70">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
                <div class="flex items-center gap-6">
                    <Link href="/" class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-400 text-slate-950 font-semibold"
                        >
                            KH
                        </div>
                        <div>
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Knowledge Hub</p>
                            <p class="text-lg font-semibold">za Timove</p>
                        </div>
                    </Link>

                    <nav class="hidden items-center gap-5 text-sm md:flex">
                        <Link :href="route('dashboard')" :class="linkClass(route().current('dashboard'))">
                            Dashboard
                        </Link>
                        <Link :href="route('questions.index')" :class="linkClass(route().current('questions.*'))">
                            Questions
                        </Link>
                        <Link :href="route('bookmarks.index')" :class="linkClass(route().current('bookmarks.index'))">
                            My Bookmarks
                        </Link>
                        <Link v-if="isAdmin" :href="route('admin.dashboard')" :class="linkClass(route().current('admin.*'))">
                            Admin
                        </Link>
                        <Link
                            v-if="isModerator"
                            :href="route('moderator.dashboard')"
                            :class="linkClass(route().current('moderator.*'))"
                        >
                            Moderator
                        </Link>
                    </nav>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('notifications.index')"
                        class="relative inline-flex items-center justify-center rounded-full border border-slate-800 bg-slate-900/70 p-2 text-slate-200 hover:border-teal-400 hover:text-teal-100"
                        :class="route().current('notifications.*') ? 'border-teal-400 text-teal-200' : ''"
                        aria-label="Notifications"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.311 6.022c1.76.645 3.62 1.1 5.454 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        <span
                            v-if="unreadNotifications"
                            class="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-500 px-1 text-[11px] font-semibold text-white"
                        >
                            {{ unreadNotifications > 99 ? '99+' : unreadNotifications }}
                        </span>
                    </Link>
                    <span
                        class="hidden items-center gap-2 rounded-full border border-slate-700 bg-slate-900/60 px-3 py-1 text-xs text-slate-300 md:inline-flex"
                    >
                        {{ roleLabels[user?.role] || 'Member' }}
                    </span>
                    <Dropdown align="right" width="48" content-classes="py-2 bg-slate-900 border border-slate-800">
                        <template #trigger>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/70 px-4 py-2 text-sm text-slate-200 transition hover:border-slate-600"
                            >
                                <span class="font-medium">{{ user?.name }}</span>
                                <svg
                                    class="h-4 w-4 text-slate-400"
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>
                        </template>

                        <template #content>
                            <div class="px-4 pb-2 text-xs uppercase tracking-[0.2em] text-slate-500">
                                Signed in as
                            </div>
                            <div class="px-4 pb-3 text-sm text-slate-200">
                                <div class="font-medium">{{ user?.name }}</div>
                                <div class="text-slate-400">{{ user?.email }}</div>
                            </div>
                            <DropdownLink :href="route('profile.show')">Profile</DropdownLink>
                            <DropdownLink v-if="isAdmin" :href="route('admin.dashboard')">Admin Panel</DropdownLink>
                            <DropdownLink v-if="isModerator" :href="route('moderator.dashboard')">Moderator Area</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">
                                Log Out
                            </DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            <slot />
        </main>
    </div>
</template>

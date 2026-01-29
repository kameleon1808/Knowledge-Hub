<script setup>
import { computed } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const user = computed(() => page.props.auth.user);
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

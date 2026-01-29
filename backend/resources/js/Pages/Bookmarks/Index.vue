<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    bookmarks: {
        type: Object,
        required: true,
    },
});

const formatDate = (value) => (value ? new Date(value).toLocaleString() : '');
</script>

<template>
    <Head title="My Bookmarks" />
    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex items-center justify-between rounded-3xl border border-slate-800 bg-slate-900/70 px-6 py-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-teal-200">Phase F</p>
                    <h1 class="text-2xl font-semibold">My Bookmarked Questions</h1>
                    <p class="text-sm text-slate-400">Quick access to questions you've saved.</p>
                </div>
                <Link
                    :href="route('questions.index')"
                    class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:border-teal-400 hover:text-teal-200"
                >
                    Browse Questions
                </Link>
            </div>

            <div class="grid gap-4">
                <div
                    v-for="question in bookmarks.data"
                    :key="question.id"
                    class="rounded-2xl border border-slate-800 bg-slate-950/60 p-6"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <Link :href="route('questions.show', question.id)" class="text-lg font-semibold text-slate-100 hover:text-teal-200">
                                {{ question.title }}
                            </Link>
                            <div class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500">
                                {{ question.author?.name || 'Unknown' }} · {{ formatDate(question.created_at) }}
                            </div>
                            <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-300">
                                <span v-if="question.category" class="rounded-full border border-slate-800 bg-slate-900/70 px-2 py-0.5">
                                    {{ question.category.name }}
                                </span>
                                <span
                                    v-for="tag in question.tags"
                                    :key="tag.id"
                                    class="rounded-full border border-slate-800 bg-slate-900/70 px-2 py-0.5"
                                >
                                    #{{ tag.name }}
                                </span>
                            </div>
                        </div>
                        <div class="text-right text-sm text-slate-400">
                            <div class="rounded-full border border-amber-400/60 bg-amber-400/10 px-3 py-1 text-amber-100">
                                ★ {{ question.bookmarks_count || 0 }}
                            </div>
                            <div class="mt-2 text-xs text-slate-300">Answers: {{ question.answers_count }}</div>
                        </div>
                    </div>
                </div>

                <div v-if="!bookmarks.data.length" class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-sm text-slate-400">
                    No bookmarks yet.
                </div>
            </div>

            <div v-if="bookmarks.links?.length" class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="link in bookmarks.links"
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

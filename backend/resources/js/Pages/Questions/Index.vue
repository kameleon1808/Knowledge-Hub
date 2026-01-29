<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    questions: {
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

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};
</script>

<template>
    <Head title="Questions" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-3 rounded-3xl border border-slate-800 bg-slate-900/70 p-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-teal-200">Phase C</p>
                    <h1 class="text-3xl font-semibold">Questions & Answers</h1>
                    <p class="mt-2 text-sm text-slate-300">Browse the latest questions from your team.</p>
                </div>
                <Link
                    v-if="can.create"
                    :href="route('questions.create')"
                    class="inline-flex items-center justify-center rounded-full bg-teal-400 px-5 py-2 text-sm font-semibold text-slate-900 transition hover:bg-teal-300"
                >
                    Ask Question
                </Link>
            </div>

            <div v-if="flashSuccess" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ flashSuccess }}
            </div>

            <div class="grid gap-4">
                <div
                    v-for="question in questions.data"
                    :key="question.id"
                    class="rounded-2xl border border-slate-800 bg-slate-950/50 p-6 transition hover:border-teal-500/50"
                >
                    <div class="flex flex-col gap-2">
                        <Link
                            :href="route('questions.show', question.id)"
                            class="text-xl font-semibold text-slate-100 hover:text-teal-200"
                        >
                            {{ question.title }}
                        </Link>
                        <div class="text-xs uppercase tracking-[0.18em] text-slate-500">
                            {{ question.author?.name || 'Unknown' }} · {{ formatDate(question.created_at) }}
                        </div>
                    </div>
                </div>

                <div v-if="!questions.data.length" class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-sm text-slate-400">
                    No questions yet. Be the first to ask one.
                </div>
            </div>

            <div v-if="questions.links?.length" class="flex flex-wrap items-center gap-2">
                <Link
                    v-for="link in questions.links"
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

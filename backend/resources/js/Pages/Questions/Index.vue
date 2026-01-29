<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    questions: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    tags: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const search = ref(props.filters.q || '');
const category = ref(props.filters.category_id ?? null);
const selectedTags = ref(props.filters.tags || []);
const status = ref(props.filters.status || '');
const datePreset = ref(props.filters.date_preset || '');
const from = ref(props.filters.from || '');
const to = ref(props.filters.to || '');

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};

const answeredLabel = (item) => (item.answers_count > 0 ? 'Answered' : 'Unanswered');

const applyFilters = () => {
    const tagIds = selectedTags.value.map((id) => Number(id));
    router.get(
        route('questions.index'),
        {
            q: search.value || undefined,
            category: category.value ? Number(category.value) : undefined,
            tags: tagIds.length ? tagIds : undefined,
            status: status.value || undefined,
            date_preset: datePreset.value || undefined,
            from: from.value || undefined,
            to: to.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true }
    );
};

const clearFilters = () => {
    search.value = '';
    category.value = null;
    selectedTags.value = [];
    status.value = '';
    datePreset.value = '';
    from.value = '';
    to.value = '';
    applyFilters();
};

const activeFilters = computed(() => {
    const chips = [];
    if (search.value) chips.push({ label: `Search: ${search.value}` });
    if (category.value) {
        const cat = props.categories.find((c) => Number(c.id) === Number(category.value));
        chips.push({ label: `Category: ${cat?.name || category.value}` });
    }
    if (selectedTags.value.length) {
        const selectedIds = selectedTags.value.map((id) => Number(id));
        const names = props.tags
            .filter((t) => selectedIds.includes(Number(t.id)))
            .map((t) => t.name)
            .join(', ');
        chips.push({ label: `Tags: ${names}` });
    }
    if (status.value) chips.push({ label: `Status: ${status.value}` });
    if (datePreset.value) chips.push({ label: `Date: ${datePreset.value}` });
    if (from.value || to.value) chips.push({ label: `Range: ${from.value || '…'} → ${to.value || '…'}` });
    return chips;
});

const bookmarkPending = ref({});

const toggleBookmark = async (question) => {
    const user = page.props.auth?.user;
    if (!user) {
        alert('Sign in to manage bookmarks.');
        return;
    }

    if (bookmarkPending.value[question.id]) return;
    bookmarkPending.value = { ...bookmarkPending.value, [question.id]: true };

    try {
        if (question.is_bookmarked) {
            const response = await window.axios.delete(route('questions.bookmark.destroy', question.id));
            question.is_bookmarked = response.data.bookmarked;
            question.bookmarks_count = response.data.bookmarks_count;
        } else {
            const response = await window.axios.post(route('questions.bookmark', question.id));
            question.is_bookmarked = response.data.bookmarked;
            question.bookmarks_count = response.data.bookmarks_count;
        }
    } finally {
        bookmarkPending.value = { ...bookmarkPending.value, [question.id]: false };
    }
};
</script>

<template>
    <Head title="Questions" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-3 rounded-3xl border border-slate-800 bg-slate-900/70 p-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-teal-200">Phase E</p>
                    <h1 class="text-3xl font-semibold">Questions & Filters</h1>
                    <p class="mt-2 text-sm text-slate-300">Search, filter by category/tag, and explore answers.</p>
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

            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-6 space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="text-sm text-slate-300">Search</label>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search titles, bodies, answers"
                            class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                        />
                    </div>
                    <div>
                        <label class="text-sm text-slate-300">Category</label>
                        <select
                            v-model="category"
                            class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                        >
                            <option :value="null">All</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-slate-300">Status</label>
                        <select
                            v-model="status"
                            class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                        >
                            <option value="">Any</option>
                            <option value="answered">Answered</option>
                            <option value="unanswered">Unanswered</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="text-sm text-slate-300">Tags</label>
                        <select
                            v-model="selectedTags"
                            multiple
                            class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                        >
                            <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                                {{ tag.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">Multiple tags use AND logic.</p>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="text-sm text-slate-300">From</label>
                            <input
                                v-model="from"
                                type="date"
                                class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                            />
                        </div>
                        <div class="flex-1">
                            <label class="text-sm text-slate-300">To</label>
                            <input
                                v-model="to"
                                type="date"
                                class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                            />
                        </div>
                    </div>
                    <div class="lg:col-span-3 flex flex-wrap gap-2">
                        <label class="text-sm text-slate-300">Quick ranges:</label>
                        <button
                            v-for="preset in [
                                { key: 'last7', label: 'Last 7 days' },
                                { key: 'last30', label: 'Last 30 days' },
                                { key: 'last90', label: 'Last 90 days' },
                            ]"
                            :key="preset.key"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs"
                            :class="datePreset === preset.key ? 'border-teal-400 bg-teal-400 text-slate-900' : 'border-slate-700 text-slate-200 hover:border-teal-400'"
                            @click="() => { datePreset = preset.key; from = ''; to = ''; applyFilters(); }"
                        >
                            {{ preset.label }}
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-full bg-teal-400 px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-teal-300"
                        @click="applyFilters"
                    >
                        Apply Filters
                    </button>
                    <button
                        type="button"
                        class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-200 hover:border-slate-500"
                        @click="clearFilters"
                    >
                        Clear all
                    </button>
                </div>

                <div v-if="activeFilters.length" class="flex flex-wrap gap-2 text-xs">
                    <span
                        v-for="chip in activeFilters"
                        :key="chip.label"
                        class="rounded-full border border-teal-400/40 bg-teal-400/10 px-3 py-1 text-teal-100"
                    >
                        {{ chip.label }}
                    </span>
                </div>
            </div>

            <div class="grid gap-4">
                <div
                    v-for="question in questions.data"
                    :key="question.id"
                    class="rounded-2xl border border-slate-800 bg-slate-950/50 p-6 transition hover:border-teal-500/50"
                >
                        <div class="flex flex-col gap-2">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <Link
                                        :href="route('questions.show', question.id)"
                                    class="text-xl font-semibold text-slate-100 hover:text-teal-200"
                                >
                                    {{ question.title }}
                                </Link>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs uppercase tracking-[0.18em] text-slate-500">
                                    <span>{{ question.author?.name || 'Unknown' }}</span>
                                    <span>· {{ formatDate(question.created_at) }}</span>
                                    <span
                                        class="rounded-full border px-2 py-0.5"
                                        :class="question.answers_count > 0 ? 'border-emerald-400/60 text-emerald-200' : 'border-amber-400/60 text-amber-200'"
                                    >
                                        {{ answeredLabel(question) }}
                                    </span>
                                    </div>
                                </div>
                                <div class="flex flex-col items-end gap-2 text-right text-sm text-slate-400">
                                    <button
                                        type="button"
                                        class="flex items-center gap-1 rounded-full border px-3 py-1 text-xs transition"
                                        :class="
                                            question.is_bookmarked
                                                ? 'border-amber-400/70 bg-amber-400/10 text-amber-100'
                                                : 'border-slate-700 text-slate-200 hover:border-amber-300 hover:text-amber-100'
                                        "
                                        :disabled="bookmarkPending[question.id]"
                                        @click="toggleBookmark(question)"
                                    >
                                        <span v-if="question.is_bookmarked">★</span>
                                        <span v-else>☆</span>
                                        <span>{{ question.bookmarks_count || 0 }}</span>
                                    </button>
                                    <div v-if="question.category" class="text-xs text-slate-300">
                                        {{ question.category.name }}
                                    </div>
                                    <div v-if="question.tags?.length" class="flex flex-wrap gap-1 justify-end mt-1">
                                        <span
                                        v-for="tag in question.tags"
                                        :key="tag.id"
                                        class="rounded-full border border-slate-800 bg-slate-900/70 px-2 py-0.5 text-[11px] text-slate-200"
                                    >
                                        #{{ tag.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!questions.data.length" class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-sm text-slate-400">
                    No questions match these filters.
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

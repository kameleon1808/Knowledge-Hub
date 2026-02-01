<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const uploadForm = useForm({
    file: null,
});
const fileInput = ref(null);
const onFileChange = (e) => {
    const f = e.target.files?.[0];
    if (f) uploadForm.file = f;
};
const submitUpload = () => {
    uploadForm.post(route('projects.knowledge-items.store', props.project.id), {
        forceFormData: true,
        onSuccess: () => {
            uploadForm.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const emailForm = useForm({
    title: '',
    from: '',
    sent_at: '',
    body_text: '',
});
const showEmailForm = ref(false);
const submitEmail = () => {
    emailForm.post(route('projects.knowledge-emails.store', props.project.id), {
        onSuccess: () => {
            emailForm.reset();
            showEmailForm.value = false;
        },
    });
};

const ragQuestion = ref('');
const ragAnswer = ref(null);
const ragCitations = ref([]);
const ragLoading = ref(false);
const ragError = ref('');
const askRag = async () => {
    if (!ragQuestion.value.trim() || ragLoading.value) return;
    ragLoading.value = true;
    ragError.value = '';
    ragAnswer.value = null;
    ragCitations.value = [];
    try {
        const token = page.props.csrf_token || '';
        const res = await fetch(route('projects.rag-ask', props.project.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ question_text: ragQuestion.value.trim() }),
            credentials: 'same-origin',
        });
        const data = await res.json();
        if (!res.ok) {
            ragError.value = data.message || 'Request failed';
            return;
        }
        ragAnswer.value = data.answer_text;
        ragCitations.value = data.citations || [];
        router.reload({ only: ['ragQueries'] });
    } finally {
        ragLoading.value = false;
    }
};

const activityMessage = (log) => {
    const meta = log.metadata || {};
    const actor = log.actor?.name || 'System';
    if (log.action === 'project.created') return `${actor} created the project`;
    if (log.action === 'knowledge_item.uploaded') return `${actor} added "${meta.title || 'item'}"`;
    if (log.action === 'knowledge_item.processed') return `Processed "${meta.title || 'item'}"`;
    if (log.action === 'knowledge_item.failed') return `Failed: ${meta.title || 'item'} (${meta.error || ''})`;
    if (log.action === 'export.generated') return `${actor} exported as ${meta.format || 'file'}`;
    if (log.action === 'rag.asked') return `${actor} asked a question`;
    return log.action;
};
const formatActivityDate = (value) =>
    value ? new Intl.DateTimeFormat('en-US', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(value)) : '';

const statusLabel = (status) => {
    const map = { pending: 'Pending', processed: 'Processed', failed: 'Failed' };
    return map[status] ?? status;
};

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
    knowledgeItems: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    ragQueries: {
        type: Array,
        default: () => [],
    },
    activityLogs: {
        type: Array,
        default: () => [],
    },
    members: {
        type: Array,
        default: () => [],
    },
    activeTab: {
        type: String,
        default: 'knowledge',
    },
    can: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);

const memberSearchQuery = ref('');
const memberSearchResults = ref([]);
const memberSearchLoading = ref(false);
let memberSearchTimeout = null;
const doMemberSearch = () => {
    const q = memberSearchQuery.value.trim();
    if (!q) {
        memberSearchResults.value = [];
        return;
    }
    memberSearchLoading.value = true;
    fetch(route('projects.members.search', props.project.id) + '?q=' + encodeURIComponent(q), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((r) => r.json())
        .then((data) => {
            memberSearchResults.value = data.users || [];
        })
        .finally(() => { memberSearchLoading.value = false; });
};
const onMemberSearchInput = () => {
    clearTimeout(memberSearchTimeout);
    memberSearchTimeout = setTimeout(doMemberSearch, 300);
};
const addMemberForm = useForm({ user_id: null });
const addMember = (user) => {
    addMemberForm.transform(() => ({ user_id: user.id })).post(route('projects.members.store', props.project.id), {
        preserveScroll: true,
        onSuccess: () => {
            memberSearchQuery.value = '';
            memberSearchResults.value = [];
        },
    });
};
const removeMember = (user) => {
    if (!confirm(`Remove ${user.name} from this project?`)) return;
    router.delete(route('projects.members.destroy', { project: props.project.id, user: user.id }), {
        preserveScroll: true,
    });
};

const tabs = [
    { key: 'knowledge', label: 'Knowledge Base' },
    { key: 'ask', label: 'Ask AI' },
    { key: 'exports', label: 'Exports' },
    { key: 'activity', label: 'Activity' },
];

const currentTab = ref(props.activeTab);

const setTab = (key) => {
    currentTab.value = key;
    router.get(route('projects.show', props.project.id), { tab: key }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head :title="project.name" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Project</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ project.name }}</h1>
                    <p v-if="project.description" class="mt-2 text-sm text-slate-400">
                        {{ project.description }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">Owner: {{ project.owner?.name ?? '—' }}</p>
                </div>
                <div class="flex gap-2">
                    <Link
                        v-if="can.update"
                        :href="route('projects.edit', project.id)"
                        class="inline-flex items-center rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('projects.index')"
                        class="inline-flex items-center rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                    >
                        Back to Projects
                    </Link>
                </div>
            </div>

            <div v-if="flashSuccess" class="rounded-xl border border-teal-800/70 bg-teal-900/30 px-4 py-3 text-sm text-teal-200">
                {{ flashSuccess }}
            </div>

            <nav class="flex gap-1 border-b border-slate-800">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    :class="[
                        'rounded-t-xl border-b-2 px-4 py-2 text-sm font-medium transition',
                        currentTab === tab.key
                            ? 'border-teal-400 text-teal-300'
                            : 'border-transparent text-slate-400 hover:text-slate-200',
                    ]"
                    @click="setTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </nav>

            <div class="min-h-[200px] rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <div v-if="currentTab === 'knowledge'" class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-200">Knowledge Base</h2>
                    <p class="text-sm text-slate-400">
                        Upload documents (PDF, DOCX, TXT) or add emails. Content is processed in the background.
                    </p>
                    <form v-if="can.addKnowledge" @submit.prevent="submitUpload" class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".pdf,.docx,.txt"
                            class="block w-full max-w-xs text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-teal-400/20 file:px-4 file:py-2 file:text-teal-300"
                            @change="onFileChange"
                        />
                        <button
                            type="submit"
                            :disabled="!uploadForm.file || uploadForm.processing"
                            class="rounded-xl border border-teal-400 bg-teal-400/10 px-4 py-2 text-sm font-medium text-teal-300 disabled:opacity-50"
                        >
                            Upload
                        </button>
                        <p v-if="uploadForm.errors.file" class="w-full text-sm text-rose-400">{{ uploadForm.errors.file }}</p>
                    </form>
                    <div v-if="can.addKnowledge" class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <button
                            v-if="!showEmailForm"
                            type="button"
                            class="text-sm font-medium text-teal-300 hover:text-teal-200"
                            @click="showEmailForm = true"
                        >
                            + Add email (paste content)
                        </button>
                        <form v-else @submit.prevent="submitEmail" class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-slate-400">Title / Subject</label>
                                <input
                                    v-model="emailForm.title"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                                    placeholder="Email subject"
                                />
                                <p v-if="emailForm.errors.title" class="mt-1 text-sm text-rose-400">{{ emailForm.errors.title }}</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-medium text-slate-400">From (optional)</label>
                                    <input
                                        v-model="emailForm.from"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                                    />
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-400">Sent at (optional)</label>
                                    <input
                                        v-model="emailForm.sent_at"
                                        type="date"
                                        class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400">Body (required)</label>
                                <textarea
                                    v-model="emailForm.body_text"
                                    rows="6"
                                    required
                                    class="mt-1 block w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100"
                                    placeholder="Paste email body text here"
                                />
                                <p v-if="emailForm.errors.body_text" class="mt-1 text-sm text-rose-400">{{ emailForm.errors.body_text }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    type="submit"
                                    :disabled="emailForm.processing"
                                    class="rounded-xl border border-teal-400 bg-teal-400/10 px-4 py-2 text-sm font-medium text-teal-300 disabled:opacity-50"
                                >
                                    Add Email
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300"
                                    @click="showEmailForm = false; emailForm.reset()"
                                >
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                    <ul class="space-y-2">
                        <li
                            v-for="item in knowledgeItems.data || []"
                            :key="item.id"
                            class="flex flex-col gap-1 rounded-xl border border-slate-800 px-3 py-2 text-sm"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <span class="truncate font-medium text-slate-200">{{ item.title }}</span>
                                    <span class="shrink-0 rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{{ item.type }}</span>
                                </div>
                                <span
                                    :class="{
                                        'text-amber-400': item.status === 'pending',
                                        'text-teal-400': item.status === 'processed',
                                        'text-rose-400': item.status === 'failed',
                                    }"
                                >
                                    {{ statusLabel(item.status) }}
                                </span>
                            </div>
                            <p v-if="item.status === 'failed' && item.error_message" class="mt-1 text-xs text-rose-400">
                                {{ item.error_message }}
                            </p>
                        </li>
                        <li v-if="!(knowledgeItems.data || []).length" class="py-4 text-center text-sm text-slate-500">
                            No documents or emails yet. Upload a file or add an email above.
                        </li>
                    </ul>
                    <div v-if="knowledgeItems.links?.length" class="mt-4 flex flex-wrap items-center gap-2">
                        <Link
                            v-for="link in knowledgeItems.links"
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
                </div>
                <div v-else-if="currentTab === 'ask'" class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-200">Ask AI</h2>
                    <p class="text-sm text-slate-400">
                        Ask questions based on this project&apos;s documents. Answers cite sources from the knowledge base.
                    </p>
                    <form v-if="can.askRag" @submit.prevent="askRag" class="space-y-3">
                        <textarea
                            v-model="ragQuestion"
                            rows="3"
                            placeholder="Your question..."
                            class="block w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                        />
                        <button
                            type="submit"
                            :disabled="!ragQuestion.trim() || ragLoading"
                            class="rounded-xl border border-teal-400 bg-teal-400/10 px-4 py-2 text-sm font-medium text-teal-300 disabled:opacity-50"
                        >
                            {{ ragLoading ? 'Asking…' : 'Ask' }}
                        </button>
                        <p v-if="ragError" class="text-sm text-rose-400">{{ ragError }}</p>
                    </form>
                    <div v-if="ragAnswer" class="rounded-xl border border-slate-800 bg-slate-950/70 p-4">
                        <p class="text-sm font-medium text-slate-300">Answer</p>
                        <p class="mt-2 whitespace-pre-wrap text-sm text-slate-200">{{ ragAnswer }}</p>
                        <div v-if="ragCitations.length" class="mt-4 border-t border-slate-800 pt-3">
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Sources</p>
                            <ul class="mt-2 space-y-2">
                                <li
                                    v-for="(c, i) in ragCitations"
                                    :key="c.id"
                                    class="rounded-lg border border-slate-800 bg-slate-900/60 p-2 text-xs text-slate-300"
                                >
                                    <span class="font-medium text-slate-400">[{{ i + 1 }}] {{ c.source_title }}</span>
                                    <p class="mt-1 truncate text-slate-500">{{ c.excerpt }}</p>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div v-if="ragQueries.length && !ragAnswer" class="space-y-2">
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Recent questions</p>
                        <ul class="space-y-2">
                            <li
                                v-for="q in ragQueries"
                                :key="q.id"
                                class="rounded-xl border border-slate-800 px-3 py-2 text-sm"
                            >
                                <p class="font-medium text-slate-200">{{ q.question_text }}</p>
                                <p v-if="q.answer_text" class="mt-1 line-clamp-2 text-slate-400">{{ q.answer_text }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ q.created_at }}</p>
                            </li>
                        </ul>
                    </div>
                </div>
                <div v-else-if="currentTab === 'exports'" class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-200">Exports</h2>
                    <p class="text-sm text-slate-400">
                        Export this project&apos;s knowledge base as Markdown or PDF.
                    </p>
                    <div v-if="can.export" class="flex flex-wrap gap-3">
                        <a
                            :href="route('projects.export.markdown', project.id)"
                            class="inline-flex items-center rounded-xl border border-teal-400 bg-teal-400/10 px-4 py-2 text-sm font-medium text-teal-300 hover:bg-teal-400/20"
                        >
                            Export as Markdown
                        </a>
                        <a
                            :href="route('projects.export.pdf', project.id)"
                            class="inline-flex items-center rounded-xl border border-slate-600 bg-slate-800/70 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-700/70"
                        >
                            Export as PDF
                        </a>
                    </div>
                </div>
                <div v-else-if="currentTab === 'activity'" class="space-y-4">
                    <h2 class="text-lg font-semibold text-slate-200">Activity</h2>
                    <p class="text-sm text-slate-400">
                        Recent events: uploads, processing, RAG questions, exports.
                    </p>
                    <ul class="space-y-2">
                        <li
                            v-for="log in activityLogs"
                            :key="log.id"
                            class="flex items-start gap-3 rounded-xl border border-slate-800 px-3 py-2 text-sm"
                        >
                            <span class="shrink-0 rounded bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{{ log.action }}</span>
                            <span class="text-slate-300">{{ activityMessage(log) }}</span>
                            <span class="shrink-0 text-xs text-slate-500">{{ formatActivityDate(log.created_at) }}</span>
                        </li>
                        <li v-if="activityLogs.length === 0" class="py-4 text-center text-sm text-slate-500">
                            No activity yet.
                        </li>
                    </ul>
                </div>
            </div>

            <div v-if="can.manageMembers" class="rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <h2 class="text-lg font-semibold text-slate-200">Members</h2>
                <div class="mt-3">
                    <label for="member-search" class="sr-only">Search users to add</label>
                    <input
                        id="member-search"
                        v-model="memberSearchQuery"
                        type="search"
                        placeholder="Search by email or name..."
                        class="w-full rounded-lg border border-slate-700 bg-slate-800/80 px-3 py-2 text-sm text-slate-200 placeholder-slate-500 focus:border-teal-500 focus:outline-none"
                        @input="onMemberSearchInput"
                    />
                    <ul v-if="memberSearchResults.length > 0" class="mt-2 max-h-40 space-y-1 overflow-y-auto rounded-lg border border-slate-700 bg-slate-800/60 p-2">
                        <li v-for="u in memberSearchResults" :key="u.id" class="flex items-center justify-between rounded px-2 py-1.5 text-sm">
                            <span class="text-slate-200">{{ u.name }} <span class="text-slate-500">({{ u.email }})</span></span>
                            <button
                                type="button"
                                class="rounded border border-teal-500/50 bg-teal-500/10 px-2 py-1 text-xs text-teal-200 hover:bg-teal-500/20"
                                :disabled="addMemberForm.processing"
                                @click="addMember(u)"
                            >
                                Add
                            </button>
                        </li>
                    </ul>
                    <p v-if="memberSearchLoading" class="mt-1 text-xs text-slate-500">Searching...</p>
                </div>
                <ul class="mt-4 space-y-2 text-sm">
                    <li v-for="m in members" :key="m.id" class="flex items-center justify-between rounded-xl border border-slate-800 px-3 py-2">
                        <span class="text-slate-200">{{ m.name }}</span>
                        <span class="flex items-center gap-2">
                            <span class="rounded-full bg-slate-800 px-2 py-0.5 text-xs text-slate-400">{{ m.role }}</span>
                            <button
                                v-if="m.role !== 'owner'"
                                type="button"
                                class="rounded border border-rose-500/40 px-2 py-0.5 text-xs text-rose-300 hover:bg-rose-500/20"
                                @click="removeMember(m)"
                            >
                                Remove
                            </button>
                        </span>
                    </li>
                </ul>
                <p v-if="flashError" class="mt-2 text-xs text-rose-400">{{ flashError }}</p>
            </div>
        </section>
    </AuthenticatedLayout>
</template>

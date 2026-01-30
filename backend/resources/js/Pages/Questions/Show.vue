<script setup>
import { computed, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';
import { getEcho } from '@/lib/echo.js';

const props = defineProps({
    question: {
        type: Object,
        required: true,
    },
    answers: {
        type: Array,
        required: true,
    },
    can: {
        type: Object,
        required: true,
    },
    attachmentConfig: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);
const authUser = computed(() => page.props.auth?.user);

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};

const clone = (value) => JSON.parse(JSON.stringify(value));

const questionData = ref(clone(props.question));
const answersData = ref(props.answers.map((answer) => clone(answer)));
const newAnswerHighlightId = ref(null);

watch(
    () => props.question,
    (value) => {
        questionData.value = clone(value);
    }
);

watch(
    () => props.answers,
    (value) => {
        answersData.value = value.map((answer) => clone(answer));
    }
);

const commentForms = reactive({
    question: { body_markdown: '', errors: {} },
    answers: {},
});

const editingComment = ref(null);
const editingErrors = ref('');

const commentFormForAnswer = (answerId) => {
    if (!commentForms.answers[answerId]) {
        commentForms.answers[answerId] = { body_markdown: '', errors: {} };
    }
    return commentForms.answers[answerId];
};

const setComments = (type, id, comments) => {
    if (type === 'question' && questionData.value.id === id) {
        questionData.value.comments = comments;
        return;
    }
    const answer = answersData.value.find((item) => item.id === id);
    if (answer) {
        answer.comments = comments;
    }
};

const handleCommentErrors = (targetForm, errorResponse) => {
    targetForm.errors = {};
    const errors = errorResponse?.response?.data?.errors;
    if (errors?.body_markdown?.length) {
        targetForm.errors.body_markdown = errors.body_markdown[0];
    }
};

const createComment = async (commentableType, commentableId, targetForm) => {
    targetForm.errors = {};
    try {
        const response = await window.axios.post(route('comments.store'), {
            commentable_type: commentableType,
            commentable_id: commentableId,
            body_markdown: targetForm.body_markdown,
        });
        setComments(commentableType, commentableId, response.data.comments);
        targetForm.body_markdown = '';
    } catch (error) {
        handleCommentErrors(targetForm, error);
    }
};

const startEditingComment = (commentableType, commentableId, comment) => {
    editingComment.value = {
        commentableType,
        commentableId,
        commentId: comment.id,
        body_markdown: comment.body_markdown,
    };
    editingErrors.value = '';
};

const cancelEditing = () => {
    editingComment.value = null;
    editingErrors.value = '';
};

const submitCommentEdit = async () => {
    if (!editingComment.value) return;
    try {
        const response = await window.axios.put(route('comments.update', editingComment.value.commentId), {
            body_markdown: editingComment.value.body_markdown,
        });
        setComments(editingComment.value.commentableType, editingComment.value.commentableId, response.data.comments);
        cancelEditing();
    } catch (error) {
        const errors = error?.response?.data?.errors;
        editingErrors.value = errors?.body_markdown?.[0] || 'Unable to update comment.';
    }
};

const deleteComment = async (commentableType, commentableId, commentId) => {
    if (!confirm('Delete this comment?')) return;
    const response = await window.axios.delete(route('comments.destroy', commentId));
    setComments(commentableType, commentableId, response.data.comments);
};

const isEditingComment = (commentId) => editingComment.value?.commentId === commentId;

const answerForm = useForm({
    body_markdown: '',
    attachments: [],
});

const selectedAnswerFiles = ref([]);

const onAnswerFilesSelected = (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;
    selectedAnswerFiles.value.push(...files);
    event.target.value = '';
};

const removeAnswerFile = (index) => {
    selectedAnswerFiles.value.splice(index, 1);
};

const submitAnswer = () => {
    answerForm.attachments = selectedAnswerFiles.value;

    answerForm.post(route('answers.store', questionData.value.id), {
        forceFormData: true,
        onSuccess: () => {
            answerForm.reset();
            selectedAnswerFiles.value = [];
        },
    });
};

const confirmDeleteQuestion = () => {
    if (confirm('Delete this question and all related answers?')) {
        router.delete(route('questions.destroy', questionData.value.id));
    }
};

const confirmDeleteAnswer = (answerId) => {
    if (confirm('Delete this answer?')) {
        router.delete(route('answers.destroy', answerId));
    }
};

const pendingVotes = ref({});
const pendingAccept = ref(false);
const voteKey = (type, id) => `${type}-${id}`;
const isVotePending = (type, id) => Boolean(pendingVotes.value[voteKey(type, id)]);

const voteDisabledReason = (authorId) => {
    if (!authUser.value) {
        return 'Sign in to vote.';
    }

    if (authUser.value.id === authorId) {
        return 'You cannot vote on your own post.';
    }

    return 'Voting is not available.';
};

const applyReputationUpdate = (reputation) => {
    if (!reputation) return;

    const updateAuthor = (author) => {
        if (!author) return;
        if (Object.prototype.hasOwnProperty.call(reputation, author.id)) {
            author.reputation = reputation[author.id];
        }
    };

    updateAuthor(questionData.value.author);
    answersData.value.forEach((answer) => updateAuthor(answer.author));
};

const bookmarkPending = ref(false);

const toggleBookmark = async () => {
    if (!authUser.value) {
        alert('Sign in to bookmark questions.');
        return;
    }
    if (bookmarkPending.value) return;

    bookmarkPending.value = true;
    try {
        if (questionData.value.is_bookmarked) {
            const response = await window.axios.delete(route('questions.bookmark.destroy', questionData.value.id));
            questionData.value.is_bookmarked = response.data.bookmarked;
            questionData.value.bookmarks_count = response.data.bookmarks_count;
        } else {
            const response = await window.axios.post(route('questions.bookmark', questionData.value.id));
            questionData.value.is_bookmarked = response.data.bookmarked;
            questionData.value.bookmarks_count = response.data.bookmarks_count;
        }
    } finally {
        bookmarkPending.value = false;
    }
};

const applyVoteResponse = (payload) => {
    if (payload.votable_type === 'question') {
        questionData.value.score = payload.score;
        questionData.value.current_user_vote = payload.current_user_vote;
    } else {
        const answer = answersData.value.find((item) => item.id === payload.votable_id);
        if (answer) {
            answer.score = payload.score;
            answer.current_user_vote = payload.current_user_vote;
        }
    }

    applyReputationUpdate(payload.reputation);
};

const handleVote = async (type, id, value, canVote) => {
    if (!canVote) return;

    const key = `${type}-${id}`;
    if (pendingVotes.value[key]) return;

    pendingVotes.value[key] = true;

    try {
        const response = await window.axios.post(route('votes.store'), {
            votable_type: type,
            votable_id: id,
            value,
        });

        applyVoteResponse(response.data);
    } finally {
        pendingVotes.value[key] = false;
    }
};

const applyAcceptResponse = (payload) => {
    questionData.value.accepted_answer_id = payload.accepted_answer_id;
    answersData.value.forEach((answer) => {
        answer.is_accepted = answer.id === payload.accepted_answer_id;
    });

    applyReputationUpdate(payload.reputation);
};

const toggleAccept = async (answer) => {
    if (!questionData.value.can?.accept) return;
    if (pendingAccept.value) return;

    pendingAccept.value = true;

    try {
        if (answer.is_accepted) {
            const response = await window.axios.delete(
                route('questions.accept.destroy', questionData.value.id)
            );
            applyAcceptResponse(response.data);
        } else {
            const response = await window.axios.post(
                route('questions.accept', [questionData.value.id, answer.id])
            );
            applyAcceptResponse(response.data);
        }
    } finally {
        pendingAccept.value = false;
    }
};

let echoChannel = null;

onMounted(() => {
    if (!authUser.value || !questionData.value?.id) return;
    const echo = getEcho();
    if (!echo) return;
    const channelName = `question.${questionData.value.id}`;
    echoChannel = echo.private(channelName);
    echoChannel.listen('.NewAnswerPosted', (payload) => {
        if (answersData.value.some((a) => a.id === payload.id)) return;
        answersData.value.push({ ...payload, isNew: true });
        newAnswerHighlightId.value = payload.id;
        setTimeout(() => {
            newAnswerHighlightId.value = null;
        }, 3000);
    });
    echoChannel.listen('.VoteUpdated', (payload) => {
        if (payload.votable_type === 'question') {
            questionData.value.score = payload.new_score;
        } else {
            const answer = answersData.value.find((a) => a.id === payload.votable_id);
            if (answer) answer.score = payload.new_score;
        }
    });
});

onUnmounted(() => {
    if (questionData.value?.id && echoChannel) {
        getEcho()?.leave(`question.${questionData.value.id}`);
    }
});
</script>

<template>
    <Head :title="questionData.title" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-8">
            <div class="flex items-start justify-between gap-6">
                <div class="flex items-start gap-6">
                    <div class="flex flex-col items-center gap-2">
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border text-xs font-semibold transition"
                            :class="
                                questionData.current_user_vote === 1
                                    ? 'border-teal-400 bg-teal-400 text-slate-900'
                                    : 'border-slate-700 text-slate-400 hover:border-teal-400 hover:text-teal-200'
                            "
                            :disabled="!questionData.can.vote || isVotePending('question', questionData.id)"
                            :title="
                                questionData.can.vote
                                    ? 'Upvote'
                                    : voteDisabledReason(questionData.author?.id)
                            "
                            @click="handleVote('question', questionData.id, 1, questionData.can.vote)"
                        >
                            ^
                        </button>
                        <span class="text-sm font-semibold text-slate-200">{{ questionData.score }}</span>
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-full border text-xs font-semibold transition"
                            :class="
                                questionData.current_user_vote === -1
                                    ? 'border-rose-400 bg-rose-400 text-slate-900'
                                    : 'border-slate-700 text-slate-400 hover:border-rose-400 hover:text-rose-200'
                            "
                            :disabled="!questionData.can.vote || isVotePending('question', questionData.id)"
                            :title="
                                questionData.can.vote
                                    ? 'Downvote'
                                    : voteDisabledReason(questionData.author?.id)
                            "
                            @click="handleVote('question', questionData.id, -1, questionData.can.vote)"
                        >
                            v
                        </button>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Question</p>
                        <h1 class="mt-2 text-3xl font-semibold">{{ questionData.title }}</h1>
                        <p class="mt-2 text-sm text-slate-400">
                            {{ questionData.author?.name || 'Unknown' }}
                            · {{ questionData.author?.reputation ?? 0 }} rep
                            · {{ formatDate(questionData.created_at) }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs">
                            <span
                                v-if="questionData.category"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900/70 px-3 py-1 text-slate-200"
                            >
                                <span class="h-1.5 w-1.5 rounded-full bg-teal-400" /> {{ questionData.category.name }}
                            </span>
                            <span
                                v-for="tag in questionData.tags || []"
                                :key="tag.id"
                                class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-900/70 px-3 py-1 text-slate-200"
                            >
                                #{{ tag.name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="flex items-center gap-2 rounded-full border px-4 py-2 text-sm transition"
                        :class="
                            questionData.is_bookmarked
                                ? 'border-amber-400/70 bg-amber-400/10 text-amber-100'
                                : 'border-slate-700 text-slate-200 hover:border-amber-400 hover:text-amber-100'
                        "
                        :disabled="bookmarkPending"
                        @click="toggleBookmark"
                    >
                        <span v-if="questionData.is_bookmarked">★</span>
                        <span v-else>☆</span>
                        <span>{{ questionData.bookmarks_count || 0 }}</span>
                    </button>
                    <Link
                        v-if="questionData.can.update"
                        :href="route('questions.edit', questionData.id)"
                        class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:border-teal-400 hover:text-teal-200"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="questionData.can.delete"
                        type="button"
                        class="rounded-full border border-rose-500/60 px-4 py-2 text-sm text-rose-200 transition hover:border-rose-400 hover:text-rose-100"
                        @click="confirmDeleteQuestion"
                    >
                        Delete
                    </button>
                </div>
            </div>

            <div v-if="flashSuccess" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ flashSuccess }}
            </div>

            <div class="rounded-3xl border border-slate-800 bg-slate-950/50 p-6">
                <div class="markdown-body" v-html="questionData.body_html" />

                <div v-if="questionData.attachments?.length" class="mt-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Attachments</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="attachment in questionData.attachments"
                            :key="attachment.id"
                            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60"
                        >
                            <img
                                :src="attachment.url"
                                :alt="attachment.original_name"
                                class="h-40 w-full object-cover"
                                loading="lazy"
                            />
                            <div class="px-4 py-3 text-xs text-slate-400">
                                {{ attachment.original_name }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <section class="rounded-3xl border border-slate-800 bg-slate-950/60 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">Comments</h2>
                    <span class="text-sm text-slate-500">{{ questionData.comments?.length || 0 }} total</span>
                </div>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="comment in questionData.comments || []"
                        :key="comment.id"
                        class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ comment.author?.name || 'Unknown' }} · {{ formatDate(comment.created_at) }}
                                </p>
                                <div v-if="isEditingComment(comment.id)" class="mt-2 space-y-2">
                                    <textarea
                                        v-model="editingComment.body_markdown"
                                        rows="3"
                                        class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                                    />
                                    <InputError :message="editingErrors" />
                                    <div class="flex gap-2">
                                        <PrimaryButton type="button" @click="submitCommentEdit">Save</PrimaryButton>
                                        <button
                                            type="button"
                                            class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-300 hover:border-slate-500"
                                            @click="cancelEditing"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="mt-2 text-sm text-slate-200 markdown-body" v-html="comment.body_html" />
                            </div>
                            <div class="flex items-center gap-3 text-xs uppercase tracking-[0.2em] text-slate-500">
                                <button
                                    v-if="comment.can.update"
                                    type="button"
                                    class="text-teal-200 hover:text-teal-100"
                                    @click="startEditingComment('question', questionData.id, comment)"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="comment.can.delete"
                                    type="button"
                                    class="text-rose-300 hover:text-rose-200"
                                    @click="deleteComment('question', questionData.id, comment.id)"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="!(questionData.comments?.length)" class="rounded-2xl border border-dashed border-slate-800 p-4 text-sm text-slate-400">
                        No comments yet.
                    </div>
                </div>

                <div v-if="can.comment" class="mt-4 space-y-2">
                    <textarea
                        v-model="commentForms.question.body_markdown"
                        rows="3"
                        placeholder="Add a comment"
                        class="w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                    />
                    <InputError :message="commentForms.question.errors.body_markdown" />
                    <div class="flex items-center gap-3">
                        <PrimaryButton type="button" @click="createComment('question', questionData.id, commentForms.question)">
                            Post Comment
                        </PrimaryButton>
                        <p class="text-xs text-slate-500">Markdown supported; keep it concise.</p>
                    </div>
                </div>
            </section>

            <section class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">Answers</h2>
                    <span class="text-sm text-slate-500">{{ answersData.length }} total</span>
                </div>

                <div v-if="answersData.length" class="grid gap-4">
                    <article
                        v-for="answer in answersData"
                        :key="answer.id"
                        class="rounded-3xl border bg-slate-950/50 p-6 transition"
                        :class="[
                            answer.is_accepted ? 'border-emerald-500/40 bg-emerald-500/10' : 'border-slate-800',
                            answer.id === newAnswerHighlightId ? 'ring-2 ring-teal-400/60' : '',
                        ]"
                    >
                        <div class="flex items-start gap-6">
                            <div class="flex flex-col items-center gap-2 pt-1">
                                <button
                                    type="button"
                                    class="flex h-9 w-9 items-center justify-center rounded-full border text-xs font-semibold transition"
                                    :class="
                                        answer.current_user_vote === 1
                                            ? 'border-teal-400 bg-teal-400 text-slate-900'
                                            : 'border-slate-700 text-slate-400 hover:border-teal-400 hover:text-teal-200'
                                    "
                                    :disabled="!answer.can.vote || isVotePending('answer', answer.id)"
                                    :title="
                                        answer.can.vote ? 'Upvote' : voteDisabledReason(answer.author?.id)
                                    "
                                    @click="handleVote('answer', answer.id, 1, answer.can.vote)"
                                >
                                    ^
                                </button>
                                <span class="text-sm font-semibold text-slate-200">{{ answer.score }}</span>
                                <button
                                    type="button"
                                    class="flex h-9 w-9 items-center justify-center rounded-full border text-xs font-semibold transition"
                                    :class="
                                        answer.current_user_vote === -1
                                            ? 'border-rose-400 bg-rose-400 text-slate-900'
                                            : 'border-slate-700 text-slate-400 hover:border-rose-400 hover:text-rose-200'
                                    "
                                    :disabled="!answer.can.vote || isVotePending('answer', answer.id)"
                                    :title="
                                        answer.can.vote ? 'Downvote' : voteDisabledReason(answer.author?.id)
                                    "
                                    @click="handleVote('answer', answer.id, -1, answer.can.vote)"
                                >
                                    v
                                </button>
                            </div>

                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.18em] text-slate-500">
                                        <span>
                                            {{ answer.author?.name || 'Unknown' }}
                                            · {{ answer.author?.reputation ?? 0 }} rep
                                            · {{ formatDate(answer.created_at) }}
                                        </span>
                                        <span
                                            v-if="answer.is_accepted"
                                            class="rounded-full border border-emerald-400/60 bg-emerald-400/10 px-3 py-1 text-[10px] text-emerald-200"
                                        >
                                            Accepted
                                        </span>
                                        <span
                                            v-if="answer.id === newAnswerHighlightId"
                                            class="rounded-full border border-teal-400/60 bg-teal-400/10 px-3 py-1 text-[10px] text-teal-200"
                                        >
                                            New answer
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="questionData.can.accept"
                                            type="button"
                                            class="rounded-full border border-emerald-500/40 px-3 py-1 text-xs uppercase tracking-[0.2em] text-emerald-200 transition hover:border-emerald-300 hover:text-emerald-100"
                                            :disabled="pendingAccept"
                                            @click="toggleAccept(answer)"
                                        >
                                            {{ answer.is_accepted ? 'Unaccept' : 'Accept' }}
                                        </button>
                                        <Link
                                            v-if="answer.can.update"
                                            :href="route('answers.edit', answer.id)"
                                            class="text-xs uppercase tracking-[0.2em] text-teal-200 hover:text-teal-100"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="answer.can.delete"
                                            type="button"
                                            class="text-xs uppercase tracking-[0.2em] text-rose-300 hover:text-rose-200"
                                            @click="confirmDeleteAnswer(answer.id)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-4 markdown-body" v-html="answer.body_html" />

                                <div v-if="answer.attachments?.length" class="mt-6">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Attachments</p>
                                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                        <div
                                            v-for="attachment in answer.attachments"
                                            :key="attachment.id"
                                            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60"
                                        >
                                            <img
                                                :src="attachment.url"
                                                :alt="attachment.original_name"
                                                class="h-36 w-full object-cover"
                                                loading="lazy"
                                            />
                                            <div class="px-4 py-3 text-xs text-slate-400">
                                                {{ attachment.original_name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-900/60 p-4">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold">Comments</h3>
                                        <span class="text-xs text-slate-500">{{ answer.comments?.length || 0 }} total</span>
                                    </div>

                                    <div class="mt-3 space-y-3">
                                        <div
                                            v-for="comment in answer.comments || []"
                                            :key="comment.id"
                                            class="rounded-xl border border-slate-800 bg-slate-950/70 p-3"
                                        >
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex-1">
                                                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">
                                                        {{ comment.author?.name || 'Unknown' }} · {{ formatDate(comment.created_at) }}
                                                    </p>
                                                    <div v-if="isEditingComment(comment.id)" class="mt-2 space-y-2">
                                                        <textarea
                                                            v-model="editingComment.body_markdown"
                                                            rows="3"
                                                            class="w-full rounded-xl border border-slate-700 bg-slate-950/80 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                                                        />
                                                        <InputError :message="editingErrors" />
                                                        <div class="flex gap-2">
                                                            <PrimaryButton type="button" @click="submitCommentEdit">Save</PrimaryButton>
                                                            <button
                                                                type="button"
                                                                class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-300 hover:border-slate-500"
                                                                @click="cancelEditing"
                                                            >
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div v-else class="mt-1 text-sm text-slate-200 markdown-body" v-html="comment.body_html" />
                                                </div>
                                                <div class="flex items-center gap-2 text-[11px] uppercase tracking-[0.18em] text-slate-500">
                                                    <button
                                                        v-if="comment.can.update"
                                                        type="button"
                                                        class="text-teal-200 hover:text-teal-100"
                                                        @click="startEditingComment('answer', answer.id, comment)"
                                                    >
                                                        Edit
                                                    </button>
                                                    <button
                                                        v-if="comment.can.delete"
                                                        type="button"
                                                        class="text-rose-300 hover:text-rose-200"
                                                        @click="deleteComment('answer', answer.id, comment.id)"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div v-if="!(answer.comments?.length)" class="rounded-xl border border-dashed border-slate-800 p-3 text-sm text-slate-400">
                                            No comments yet.
                                        </div>
                                    </div>

                                    <div v-if="can.comment" class="mt-3 space-y-2">
                                        <textarea
                                            v-model="commentFormForAnswer(answer.id).body_markdown"
                                            rows="3"
                                            placeholder="Add a comment"
                                            class="w-full rounded-xl border border-slate-800 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                                        />
                                        <InputError :message="commentFormForAnswer(answer.id).errors.body_markdown" />
                                        <div class="flex items-center gap-3">
                                            <PrimaryButton
                                                type="button"
                                                @click="createComment('answer', answer.id, commentFormForAnswer(answer.id))"
                                            >
                                                Post Comment
                                            </PrimaryButton>
                                            <p class="text-xs text-slate-500">Markdown supported.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-2xl border border-dashed border-slate-800 p-6 text-center text-sm text-slate-400">
                    No answers yet. Be the first to respond.
                </div>
            </section>

            <section v-if="can.answer" class="rounded-3xl border border-slate-800 bg-slate-950/50 p-6">
                <h2 class="text-2xl font-semibold">Your Answer</h2>
                <p class="mt-2 text-sm text-slate-400">Share a clear response with supporting details.</p>

                <form class="mt-6 flex flex-col gap-6" @submit.prevent="submitAnswer">
                    <div>
                        <InputLabel value="Answer" />
                        <MarkdownEditor v-model="answerForm.body_markdown" />
                        <InputError class="mt-2" :message="answerForm.errors.body_markdown" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <InputLabel value="Images" />
                            <span class="text-xs text-slate-500">
                                Max {{ attachmentConfig.maxSizeKb }}KB · {{ attachmentConfig.allowedMimes.join(', ') }}
                            </span>
                        </div>
                        <input
                            type="file"
                            class="mt-3 w-full rounded-xl border border-slate-800 bg-slate-950/60 text-sm text-slate-300 file:mr-4 file:rounded-full file:border-0 file:bg-teal-400 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-900"
                            multiple
                            accept="image/*"
                            @change="onAnswerFilesSelected"
                        />
                        <InputError class="mt-2" :message="answerForm.errors.attachments" />
                        <InputError
                            v-if="answerForm.errors['attachments.0']"
                            class="mt-2"
                            :message="answerForm.errors['attachments.0']"
                        />

                        <div v-if="selectedAnswerFiles.length" class="mt-4 grid gap-2">
                            <div
                                v-for="(file, index) in selectedAnswerFiles"
                                :key="file.name + index"
                                class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm"
                            >
                                <span class="truncate">{{ file.name }}</span>
                                <button
                                    type="button"
                                    class="text-xs uppercase tracking-[0.2em] text-rose-300 hover:text-rose-200"
                                    @click="removeAnswerFile(index)"
                                >
                                    Remove
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="answerForm.processing">Post Answer</PrimaryButton>
                        <Link
                            :href="route('questions.index')"
                            class="text-sm text-slate-400 hover:text-slate-200"
                        >
                            Back to Questions
                        </Link>
                    </div>
                </form>
            </section>
        </section>
    </AuthenticatedLayout>
</template>

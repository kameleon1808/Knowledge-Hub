<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';

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

const formatDate = (value) => {
    if (!value) return '';
    return new Date(value).toLocaleString();
};

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

    answerForm.post(route('answers.store', props.question.id), {
        forceFormData: true,
        onSuccess: () => {
            answerForm.reset();
            selectedAnswerFiles.value = [];
        },
    });
};

const confirmDeleteQuestion = () => {
    if (confirm('Delete this question and all related answers?')) {
        router.delete(route('questions.destroy', props.question.id));
    }
};

const confirmDeleteAnswer = (answerId) => {
    if (confirm('Delete this answer?')) {
        router.delete(route('answers.destroy', answerId));
    }
};
</script>

<template>
    <Head :title="question.title" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-8">
            <div class="flex items-start justify-between gap-6">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Question</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ question.title }}</h1>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ question.author?.name || 'Unknown' }} · {{ formatDate(question.created_at) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="question.can.update"
                        :href="route('questions.edit', question.id)"
                        class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-200 transition hover:border-teal-400 hover:text-teal-200"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="question.can.delete"
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
                <div class="markdown-body" v-html="question.body_html" />

                <div v-if="question.attachments?.length" class="mt-6">
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Attachments</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="attachment in question.attachments"
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

            <section class="flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-semibold">Answers</h2>
                    <span class="text-sm text-slate-500">{{ answers.length }} total</span>
                </div>

                <div v-if="answers.length" class="grid gap-4">
                    <article
                        v-for="answer in answers"
                        :key="answer.id"
                        class="rounded-3xl border border-slate-800 bg-slate-950/50 p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-500">
                                {{ answer.author?.name || 'Unknown' }} · {{ formatDate(answer.created_at) }}
                            </div>
                            <div class="flex items-center gap-2">
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

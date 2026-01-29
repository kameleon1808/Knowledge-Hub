<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import MarkdownEditor from '@/Components/MarkdownEditor.vue';

const props = defineProps({
    answer: {
        type: Object,
        required: true,
    },
    question: {
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

const form = useForm({
    body_markdown: props.answer.body_markdown,
    attachments: [],
    remove_attachments: [],
});

const existingAttachments = ref([...props.answer.attachments]);
const selectedFiles = ref([]);

const onFilesSelected = (event) => {
    const files = Array.from(event.target.files || []);
    if (!files.length) return;
    selectedFiles.value.push(...files);
    event.target.value = '';
};

const removeSelected = (index) => {
    selectedFiles.value.splice(index, 1);
};

const removeExisting = (attachment) => {
    if (!form.remove_attachments.includes(attachment.id)) {
        form.remove_attachments.push(attachment.id);
    }
    existingAttachments.value = existingAttachments.value.filter((item) => item.id !== attachment.id);
};

const submit = () => {
    form.attachments = selectedFiles.value;

    form.put(route('answers.update', props.answer.id), {
        forceFormData: true,
        onSuccess: () => {
            selectedFiles.value = [];
        },
    });
};
</script>

<template>
    <Head title="Edit Answer" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Answering</p>
                    <h1 class="mt-2 text-3xl font-semibold">{{ question.title }}</h1>
                </div>
                <Link
                    :href="route('questions.show', question.id)"
                    class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:border-slate-500 hover:text-slate-100"
                >
                    Back to Question
                </Link>
            </div>

            <div v-if="flashSuccess" class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ flashSuccess }}
            </div>

            <form class="flex flex-col gap-6" @submit.prevent="submit">
                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-6">
                    <InputLabel value="Answer" />
                    <MarkdownEditor v-model="form.body_markdown" />
                    <InputError class="mt-2" :message="form.errors.body_markdown" />
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-950/50 p-6">
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
                        @change="onFilesSelected"
                    />
                    <InputError class="mt-2" :message="form.errors.attachments" />
                    <InputError
                        v-if="form.errors['attachments.0']"
                        class="mt-2"
                        :message="form.errors['attachments.0']"
                    />

                    <div v-if="existingAttachments.length" class="mt-4 grid gap-2">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Existing images</p>
                        <div
                            v-for="attachment in existingAttachments"
                            :key="attachment.id"
                            class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm"
                        >
                            <span class="truncate">{{ attachment.original_name }}</span>
                            <button
                                type="button"
                                class="text-xs uppercase tracking-[0.2em] text-rose-300 hover:text-rose-200"
                                @click="removeExisting(attachment)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

                    <div v-if="selectedFiles.length" class="mt-4 grid gap-2">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">New uploads</p>
                        <div
                            v-for="(file, index) in selectedFiles"
                            :key="file.name + index"
                            class="flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900/60 px-4 py-2 text-sm"
                        >
                            <span class="truncate">{{ file.name }}</span>
                            <button
                                type="button"
                                class="text-xs uppercase tracking-[0.2em] text-rose-300 hover:text-rose-200"
                                @click="removeSelected(index)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <PrimaryButton :disabled="form.processing">Save Answer</PrimaryButton>
                    <Link
                        :href="route('questions.show', question.id)"
                        class="text-sm text-slate-400 hover:text-slate-200"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

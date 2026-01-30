<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const form = useForm({
    name: '',
    description: '',
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="New Project" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold">New Project</h1>
                    <p class="mt-2 text-sm text-slate-400">Create a project to group documents and emails for RAG.</p>
                </div>
                <Link
                    :href="route('projects.index')"
                    class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:border-slate-500"
                >
                    Back to Projects
                </Link>
            </div>

            <form @submit.prevent="submit" class="max-w-xl space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError class="mt-1" :message="form.errors.name" />
                </div>
                <div>
                    <InputLabel for="description" value="Description (optional)" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                    />
                    <InputError class="mt-1" :message="form.errors.description" />
                </div>
                <div class="flex gap-3">
                    <PrimaryButton :disabled="form.processing">Create Project</PrimaryButton>
                    <Link
                        :href="route('projects.index')"
                        class="inline-flex items-center rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

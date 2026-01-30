<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.project.name,
    description: props.project.description ?? '',
});

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const submit = () => {
    form.put(route('projects.update', props.project.id), {
        onSuccess: () => {},
    });
};
</script>

<template>
    <Head :title="`Edit: ${project.name}`" />

    <AuthenticatedLayout>
        <section class="flex flex-col gap-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-semibold">Edit Project</h1>
                    <p class="mt-2 text-sm text-slate-400">Update project name and description.</p>
                </div>
                <Link
                    :href="route('projects.show', project.id)"
                    class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-300 transition hover:border-slate-500"
                >
                    Back to Project
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
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                    <Link
                        :href="route('projects.show', project.id)"
                        class="inline-flex items-center rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </section>
    </AuthenticatedLayout>
</template>

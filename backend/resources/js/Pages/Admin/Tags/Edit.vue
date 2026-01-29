<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    tag: {
        type: Object,
        required: true,
    },
});

defineOptions({
    layout: AdminLayout,
});

const form = useForm({
    name: props.tag.name,
});

const submit = () => {
    form.put(route('admin.tags.update', props.tag.id));
};
</script>

<template>
    <Head title="Edit Tag" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Tags</p>
                <h1 class="mt-1 text-3xl font-semibold">Edit Tag</h1>
            </div>
            <Link
                :href="route('admin.tags.index')"
                class="rounded-full border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:border-slate-500"
            >
                Back
            </Link>
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/70 p-6 space-y-3">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" type="text" class="mt-2 w-full" />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>
                <p class="text-xs text-slate-500">Slug updates automatically when the name changes.</p>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="rounded-full bg-teal-400 px-5 py-2 text-sm font-semibold text-slate-900 hover:bg-teal-300"
                    :disabled="form.processing"
                >
                    Save Changes
                </button>
                <Link :href="route('admin.tags.index')" class="text-sm text-slate-400 hover:text-slate-100">
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>

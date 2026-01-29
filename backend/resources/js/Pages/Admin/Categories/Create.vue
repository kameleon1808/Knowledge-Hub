<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    parents: {
        type: Array,
        default: () => [],
    },
});

defineOptions({
    layout: AdminLayout,
});

const form = useForm({
    name: '',
    description: '',
    parent_id: null,
});

const submit = () => {
    form.post(route('admin.categories.store'));
};
</script>

<template>
    <Head title="New Category" />

    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Categories</p>
                <h1 class="mt-1 text-3xl font-semibold">Create Category</h1>
            </div>
            <Link
                :href="route('admin.categories.index')"
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
                <div>
                    <InputLabel for="parent_id" value="Parent" />
                    <select
                        id="parent_id"
                        v-model="form.parent_id"
                        class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                    >
                        <option :value="null">None</option>
                        <option v-for="parent in parents" :key="parent.id" :value="parent.id">
                            {{ parent.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.parent_id" />
                </div>
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-2 w-full rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="rounded-full bg-teal-400 px-5 py-2 text-sm font-semibold text-slate-900 hover:bg-teal-300"
                    :disabled="form.processing"
                >
                    Create
                </button>
                <Link :href="route('admin.categories.index')" class="text-sm text-slate-400 hover:text-slate-100">
                    Cancel
                </Link>
            </div>
        </form>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    roleLabels: {
        type: Object,
        default: () => ({}),
    },
});

defineOptions({
    layout: AdminLayout,
});

const page = usePage();
const flash = computed(() => page.props.flash?.success);

const form = useForm({
    role: props.user.role,
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit User" />

    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Edit User</p>
                <h1 class="mt-2 text-2xl font-semibold">{{ user.name }}</h1>
                <p class="mt-1 text-sm text-slate-400">{{ user.email }}</p>
            </div>
            <Link
                :href="route('admin.users.index')"
                class="rounded-full border border-slate-700 px-4 py-2 text-xs uppercase tracking-widest text-slate-300 hover:border-slate-500"
            >
                Back to users
            </Link>
        </div>

        <div v-if="flash" class="rounded-2xl border border-teal-500/40 bg-teal-500/10 px-4 py-3 text-sm text-teal-200">
            {{ flash }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="rounded-2xl border border-slate-800 bg-slate-950/60 p-6">
                <InputLabel for="role" value="Role" />
                <select
                    id="role"
                    v-model="form.role"
                    class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2 text-sm text-slate-100 focus:border-teal-400 focus:ring-1 focus:ring-teal-400"
                >
                    <option v-for="role in roles" :key="role" :value="role">
                        {{ roleLabels[role] || role }}
                    </option>
                </select>
                <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <div class="flex items-center gap-3">
                <PrimaryButton :disabled="form.processing" :class="{ 'opacity-25': form.processing }">
                    Save role
                </PrimaryButton>
                <SecondaryButton type="button" @click="form.reset('role')">
                    Reset
                </SecondaryButton>
            </div>
        </form>
    </div>
</template>

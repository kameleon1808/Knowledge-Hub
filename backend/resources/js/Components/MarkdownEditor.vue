<script setup>
import { computed, ref } from 'vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';

marked.setOptions({
    headerIds: false,
    mangle: false,
});

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Write in Markdown…',
    },
});

const emit = defineEmits(['update:modelValue']);
const mode = ref('write');

const previewHtml = computed(() =>
    DOMPurify.sanitize(marked.parse(props.modelValue || '')),
);

const updateValue = (event) => {
    emit('update:modelValue', event.target.value);
};
</script>

<template>
    <div class="rounded-2xl border border-slate-800 bg-slate-950/60">
        <div class="flex items-center gap-2 border-b border-slate-800 px-4 py-2 text-xs uppercase tracking-[0.2em] text-slate-400">
            <button
                type="button"
                class="rounded-full px-3 py-1 transition"
                :class="mode === 'write' ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:text-slate-200'"
                @click="mode = 'write'"
            >
                Write
            </button>
            <button
                type="button"
                class="rounded-full px-3 py-1 transition"
                :class="mode === 'preview' ? 'bg-slate-800 text-slate-100' : 'text-slate-400 hover:text-slate-200'"
                @click="mode = 'preview'"
            >
                Preview
            </button>
        </div>

        <div class="p-4">
            <textarea
                v-if="mode === 'write'"
                :value="modelValue"
                :placeholder="placeholder"
                class="min-h-[220px] w-full rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-500 focus:border-teal-400 focus:outline-none focus:ring-1 focus:ring-teal-400"
                @input="updateValue"
            />
            <div
                v-else
                class="markdown-body rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-sm text-slate-100"
                v-html="previewHtml"
            />
        </div>
    </div>
</template>

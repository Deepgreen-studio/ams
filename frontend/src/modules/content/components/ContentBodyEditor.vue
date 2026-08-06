<template>
  <div class="space-y-3">
    <div class="inline-flex rounded-lg border border-slate-200 bg-white p-1">
      <button
        v-for="mode in modes"
        :key="mode.value"
        type="button"
        class="rounded-md px-3 py-1.5 text-xs font-medium"
        :class="
          modelValue === mode.value
            ? 'bg-brand-50 text-brand-700'
            : 'text-slate-600 hover:bg-slate-50'
        "
        @click="$emit('update:modelValue', mode.value)"
      >
        {{ mode.label }}
      </button>
    </div>

    <RichTextEditor
      v-if="modelValue === 'rich'"
      :model-value="html"
      :json-value="json"
      @update:model-value="$emit('update:html', $event)"
      @update:json-value="$emit('update:json', $event)"
      @upload-error="$emit('upload-error', $event)"
    />

    <div
      v-else-if="modelValue === 'markdown'"
      class="overflow-hidden rounded-xl border border-slate-200 bg-white"
    >
      <div
        class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-500"
      >
        Markdown
      </div>
      <textarea
        :value="markdown"
        rows="18"
        class="w-full resize-y border-0 px-4 py-3 font-mono text-sm outline-none focus:ring-0"
        placeholder="# Heading&#10;&#10;Write markdown content…"
        @input="onMarkdownInput"
      />
    </div>

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div
        class="border-b border-slate-200 bg-slate-50 px-3 py-2 text-xs font-medium uppercase tracking-wide text-slate-500"
      >
        HTML
      </div>
      <textarea
        :value="html"
        rows="18"
        class="w-full resize-y border-0 px-4 py-3 font-mono text-sm outline-none focus:ring-0"
        placeholder="<p>HTML body…</p>"
        @input="onHtmlInput"
      />
    </div>
  </div>
</template>

<script setup>
import { marked } from 'marked';
import RichTextEditor from '@/modules/content/components/RichTextEditor.vue';

defineProps({
  modelValue: { type: String, default: 'rich' },
  html: { type: String, default: '' },
  markdown: { type: String, default: '' },
  json: { type: Object, default: null },
});

const emit = defineEmits([
  'update:modelValue',
  'update:html',
  'update:markdown',
  'update:json',
  'upload-error',
]);

const modes = [
  { value: 'rich', label: 'Rich Text' },
  { value: 'markdown', label: 'Markdown' },
  { value: 'html', label: 'HTML' },
];

function onMarkdownInput(event) {
  const value = event.target.value;
  emit('update:markdown', value);
  emit('update:html', marked.parse(value || ''));
  emit('update:json', null);
}

function onHtmlInput(event) {
  const value = event.target.value;
  emit('update:html', value);
  emit('update:markdown', value);
  emit('update:json', null);
}
</script>

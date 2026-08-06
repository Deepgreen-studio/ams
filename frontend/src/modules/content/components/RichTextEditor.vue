<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
      <button
        v-for="action in actions"
        :key="action.key"
        type="button"
        class="rounded px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
        :class="{ 'bg-white': action.active?.() }"
        :title="action.label"
        @click="action.run"
      >
        {{ action.label }}
      </button>
      <label
        class="ml-1 cursor-pointer rounded px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
      >
        Image
        <input type="file" accept="image/*" class="hidden" @change="onImageUpload" />
      </label>
      <button
        type="button"
        class="rounded px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
        @click="embedVideo"
      >
        Video
      </button>
      <button
        type="button"
        class="rounded px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
        @click="insertComponent"
      >
        Component
      </button>
    </div>
    <editor-content
      :editor="editor"
      class="prose max-w-none min-h-[22rem] px-4 py-3 focus:outline-none"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import Youtube from '@tiptap/extension-youtube';
import { Table } from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';
import CodeBlockLowlight from '@tiptap/extension-code-block-lowlight';
import { common, createLowlight } from 'lowlight';
import { CustomComponent } from '@/modules/content/editor/CustomComponent';
import { contentService } from '@/modules/content/services/contentService';

const props = defineProps({
  modelValue: { type: String, default: '' },
  jsonValue: { type: Object, default: null },
  editable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue', 'update:jsonValue', 'upload-error']);

const lowlight = createLowlight(common);

const editor = useEditor({
  editable: props.editable,
  content: props.jsonValue || props.modelValue || '',
  extensions: [
    StarterKit.configure({ codeBlock: false }),
    Underline,
    Link.configure({ openOnClick: false, autolink: true }),
    Image.configure({ inline: false, allowBase64: false }),
    Youtube.configure({ controls: true, modestBranding: true }),
    Table.configure({ resizable: true }),
    TableRow,
    TableHeader,
    TableCell,
    CodeBlockLowlight.configure({ lowlight }),
    Placeholder.configure({ placeholder: 'Start writing content…' }),
    CustomComponent,
  ],
  onUpdate: ({ editor: current }) => {
    emit('update:modelValue', current.getHTML());
    emit('update:jsonValue', current.getJSON());
  },
});

const actions = computed(() => {
  if (!editor.value) return [];
  return [
    {
      key: 'bold',
      label: 'Bold',
      active: () => editor.value.isActive('bold'),
      run: () => editor.value.chain().focus().toggleBold().run(),
    },
    {
      key: 'italic',
      label: 'Italic',
      active: () => editor.value.isActive('italic'),
      run: () => editor.value.chain().focus().toggleItalic().run(),
    },
    {
      key: 'underline',
      label: 'Underline',
      active: () => editor.value.isActive('underline'),
      run: () => editor.value.chain().focus().toggleUnderline().run(),
    },
    {
      key: 'h2',
      label: 'H2',
      active: () => editor.value.isActive('heading', { level: 2 }),
      run: () => editor.value.chain().focus().toggleHeading({ level: 2 }).run(),
    },
    {
      key: 'h3',
      label: 'H3',
      active: () => editor.value.isActive('heading', { level: 3 }),
      run: () => editor.value.chain().focus().toggleHeading({ level: 3 }).run(),
    },
    {
      key: 'bullet',
      label: 'List',
      active: () => editor.value.isActive('bulletList'),
      run: () => editor.value.chain().focus().toggleBulletList().run(),
    },
    {
      key: 'ordered',
      label: 'Ordered',
      active: () => editor.value.isActive('orderedList'),
      run: () => editor.value.chain().focus().toggleOrderedList().run(),
    },
    {
      key: 'quote',
      label: 'Quote',
      active: () => editor.value.isActive('blockquote'),
      run: () => editor.value.chain().focus().toggleBlockquote().run(),
    },
    {
      key: 'code',
      label: 'Code',
      active: () => editor.value.isActive('codeBlock'),
      run: () => editor.value.chain().focus().toggleCodeBlock().run(),
    },
    {
      key: 'table',
      label: 'Table',
      active: () => editor.value.isActive('table'),
      run: () =>
        editor.value.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
    },
    { key: 'link', label: 'Link', active: () => editor.value.isActive('link'), run: setLink },
    { key: 'hr', label: 'HR', run: () => editor.value.chain().focus().setHorizontalRule().run() },
  ];
});

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value || editor.value.isDestroyed) return;
    const current = editor.value.getHTML();
    if (value !== current && !props.jsonValue) {
      editor.value.commands.setContent(value || '', { emitUpdate: false });
    }
  },
);

watch(
  () => props.jsonValue,
  (value) => {
    if (!editor.value || editor.value.isDestroyed || !value) return;
    const current = JSON.stringify(editor.value.getJSON());
    if (JSON.stringify(value) !== current) {
      editor.value.commands.setContent(value, { emitUpdate: false });
    }
  },
);

function setLink() {
  const previous = editor.value.getAttributes('link').href;
  const url = window.prompt('Enter URL', previous || 'https://');
  if (url === null) return;
  if (url === '') {
    editor.value.chain().focus().extendMarkRange('link').unsetLink().run();
    return;
  }
  editor.value.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
}

function embedVideo() {
  const url = window.prompt('YouTube URL', 'https://www.youtube.com/watch?v=');
  if (!url) return;
  editor.value.commands.setYoutubeVideo({ src: url });
}

function insertComponent() {
  const title = window.prompt('Component title', 'Callout') || 'Callout';
  const body = window.prompt('Component body', 'Supporting content for apps and websites.') || '';
  editor.value.commands.insertCustomComponent({ component: 'callout', title, body });
}

async function onImageUpload(event) {
  const file = event.target.files?.[0];
  event.target.value = '';
  if (!file || !editor.value) return;

  try {
    const formData = new FormData();
    formData.append('file', file);
    const { data } = await contentService.uploadMedia(formData);
    const url = data.data?.media?.url;
    if (url) {
      editor.value.chain().focus().setImage({ src: url, alt: file.name }).run();
    }
  } catch (err) {
    emit('upload-error', err?.message || 'Unable to upload image');
  }
}

onBeforeUnmount(() => {
  editor.value?.destroy();
});
</script>

<style>
.ProseMirror {
  min-height: 20rem;
  outline: none;
}
.ProseMirror table {
  border-collapse: collapse;
  width: 100%;
  margin: 1rem 0;
}
.ProseMirror td,
.ProseMirror th {
  border: 1px solid #cbd5e1;
  padding: 0.4rem 0.6rem;
}
.ProseMirror pre {
  background: #0f172a;
  color: #e2e8f0;
  border-radius: 0.5rem;
  padding: 0.75rem 1rem;
  overflow-x: auto;
}
.ProseMirror img {
  max-width: 100%;
  height: auto;
  border-radius: 0.5rem;
}
.ProseMirror iframe {
  max-width: 100%;
  border-radius: 0.5rem;
}
</style>

<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="flex flex-wrap items-center gap-1 border-b border-slate-200 bg-slate-50 p-2">
      <button
        v-for="action in actions"
        :key="action.key"
        type="button"
        class="rounded px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
        :class="{ 'bg-white ring-1 ring-slate-200': action.active?.() }"
        @click="action.run"
      >
        {{ action.label }}
      </button>
    </div>
    <editor-content :editor="editor" class="prose max-w-none min-h-[10rem] px-3 py-2 focus:outline-none" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Write your reply…' },
  editable: { type: Boolean, default: true },
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
  editable: props.editable,
  content: props.modelValue || '',
  extensions: [
    StarterKit,
    Underline,
    Link.configure({ openOnClick: false, autolink: true }),
    Placeholder.configure({ placeholder: props.placeholder }),
  ],
  onUpdate: ({ editor: current }) => {
    emit('update:modelValue', current.getHTML());
  },
});

watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) return;
    const current = editor.value.getHTML();
    if (value !== current) {
      editor.value.commands.setContent(value || '', false);
    }
  }
);

watch(
  () => props.editable,
  (value) => editor.value?.setEditable(value)
);

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const actions = computed(() => [
  {
    key: 'bold',
    label: 'Bold',
    active: () => editor.value?.isActive('bold'),
    run: () => editor.value?.chain().focus().toggleBold().run(),
  },
  {
    key: 'italic',
    label: 'Italic',
    active: () => editor.value?.isActive('italic'),
    run: () => editor.value?.chain().focus().toggleItalic().run(),
  },
  {
    key: 'underline',
    label: 'Underline',
    active: () => editor.value?.isActive('underline'),
    run: () => editor.value?.chain().focus().toggleUnderline().run(),
  },
  {
    key: 'bullet',
    label: 'Bullets',
    active: () => editor.value?.isActive('bulletList'),
    run: () => editor.value?.chain().focus().toggleBulletList().run(),
  },
  {
    key: 'ordered',
    label: 'Numbered',
    active: () => editor.value?.isActive('orderedList'),
    run: () => editor.value?.chain().focus().toggleOrderedList().run(),
  },
  {
    key: 'link',
    label: 'Link',
    active: () => editor.value?.isActive('link'),
    run: () => {
      const previous = editor.value?.getAttributes('link').href || '';
      const url = window.prompt('URL', previous);
      if (url === null) return;
      if (url === '') {
        editor.value?.chain().focus().extendMarkRange('link').unsetLink().run();
        return;
      }
      editor.value?.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    },
  },
]);
</script>

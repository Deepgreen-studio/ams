<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-100 bg-zinc-50/80 px-2 py-1.5">
      <button
        v-for="action in actions"
        :key="action.key"
        type="button"
        class="inline-flex h-8 min-w-8 items-center justify-center rounded-[8px] px-2 text-slate-600 transition hover:bg-white hover:text-slate-900"
        :class="action.active?.() ? 'bg-white text-brand-700 ring-1 ring-zinc-200' : ''"
        :title="action.label"
        :disabled="!editable"
        @click="action.run"
      >
        <component
          :is="action.icon"
          v-if="action.icon"
          class="h-4 w-4"
        />
        <span v-else class="text-xs font-semibold" :class="action.markClass">
          {{ action.mark }}
        </span>
      </button>
    </div>
    <editor-content :editor="editor" class="ticket-reply-editor" />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, watch } from 'vue';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import Underline from '@tiptap/extension-underline';
import { LinkIcon, ListBulletIcon, NumberedListIcon } from '@heroicons/vue/24/outline';

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
    Placeholder.configure({
      placeholder: () => props.placeholder,
      emptyEditorClass: 'is-editor-empty',
    }),
  ],
  editorProps: {
    attributes: {
      class: 'prose prose-sm max-w-none min-h-[9rem] px-3.5 py-3 text-sm text-slate-800 focus:outline-none',
    },
  },
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

watch(
  () => props.placeholder,
  () => editor.value?.view.dispatch(editor.value.state.tr)
);

onBeforeUnmount(() => {
  editor.value?.destroy();
});

const actions = computed(() => [
  {
    key: 'bold',
    label: 'Bold',
    mark: 'B',
    markClass: 'font-bold',
    active: () => editor.value?.isActive('bold'),
    run: () => editor.value?.chain().focus().toggleBold().run(),
  },
  {
    key: 'italic',
    label: 'Italic',
    mark: 'I',
    markClass: 'italic',
    active: () => editor.value?.isActive('italic'),
    run: () => editor.value?.chain().focus().toggleItalic().run(),
  },
  {
    key: 'underline',
    label: 'Underline',
    mark: 'U',
    markClass: 'underline',
    active: () => editor.value?.isActive('underline'),
    run: () => editor.value?.chain().focus().toggleUnderline().run(),
  },
  {
    key: 'bullet',
    label: 'Bullets',
    icon: ListBulletIcon,
    active: () => editor.value?.isActive('bulletList'),
    run: () => editor.value?.chain().focus().toggleBulletList().run(),
  },
  {
    key: 'ordered',
    label: 'Numbered',
    icon: NumberedListIcon,
    active: () => editor.value?.isActive('orderedList'),
    run: () => editor.value?.chain().focus().toggleOrderedList().run(),
  },
  {
    key: 'link',
    label: 'Link',
    icon: LinkIcon,
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

<style scoped>
.ticket-reply-editor :deep(.ProseMirror) {
  outline: none;
}

.ticket-reply-editor :deep(.ProseMirror p.is-empty:first-child::before),
.ticket-reply-editor :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  color: #94a3b8;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}
</style>

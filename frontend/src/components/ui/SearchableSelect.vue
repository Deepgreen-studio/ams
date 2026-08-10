<template>
  <div ref="rootRef" class="relative">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-2 text-left outline-none transition"
      :class="[buttonClass, disabled ? 'cursor-not-allowed opacity-60' : '']"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click="toggle"
    >
      <span class="min-w-0 flex-1 truncate" :class="selectedLabel ? 'text-slate-900' : 'text-slate-400'">
        {{ selectedLabel || placeholder }}
      </span>
      <svg
        class="h-4 w-4 shrink-0 text-slate-400 transition"
        :class="open ? 'rotate-180' : ''"
        viewBox="0 0 20 20"
        fill="currentColor"
        aria-hidden="true"
      >
        <path
          fill-rule="evenodd"
          d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z"
          clip-rule="evenodd"
        />
      </svg>
    </button>

    <div
      v-if="open"
      class="absolute z-30 mt-1.5 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg shadow-slate-200/70"
      role="listbox"
    >
      <div class="border-b border-slate-100 p-2">
        <input
          ref="searchRef"
          v-model="query"
          type="search"
          class="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 px-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:bg-white focus:ring-2 focus:ring-brand-500/10"
          :placeholder="searchPlaceholder"
          @keydown.esc.prevent="close"
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="selectHighlighted"
        />
      </div>

      <ul class="max-h-56 overflow-y-auto py-1">
        <li v-if="filteredOptions.length === 0" class="px-3 py-2.5 text-sm text-slate-500">
          No matches found
        </li>
        <li
          v-for="(option, index) in filteredOptions"
          :key="option.value"
          class="cursor-pointer px-3 py-2 text-sm transition"
          :class="optionClass(option, index)"
          role="option"
          :aria-selected="option.value === modelValue"
          @mousedown.prevent="select(option.value)"
          @mouseenter="highlightedIndex = index"
        >
          {{ option.label }}
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, null],
    default: '',
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: 'Select…',
  },
  searchPlaceholder: {
    type: String,
    default: 'Search…',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  buttonClass: {
    type: String,
    default:
      'h-12 rounded-xl border border-slate-200 bg-white px-3.5 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10',
  },
});

const emit = defineEmits(['update:modelValue']);

const rootRef = ref(null);
const searchRef = ref(null);
const open = ref(false);
const query = ref('');
const highlightedIndex = ref(0);

const selectedLabel = computed(() => {
  const match = props.options.find((option) => option.value === props.modelValue);
  return match?.label || (props.modelValue ? String(props.modelValue) : '');
});

const filteredOptions = computed(() => {
  const term = query.value.trim().toLowerCase();
  if (!term) {
    return props.options;
  }

  return props.options.filter((option) => {
    const label = String(option.label || '').toLowerCase();
    const value = String(option.value || '').toLowerCase();
    return label.includes(term) || value.includes(term);
  });
});

watch(filteredOptions, () => {
  highlightedIndex.value = 0;
});

watch(open, async (isOpen) => {
  if (!isOpen) {
    query.value = '';
    return;
  }

  await nextTick();
  searchRef.value?.focus();

  const selectedIndex = filteredOptions.value.findIndex((option) => option.value === props.modelValue);
  highlightedIndex.value = selectedIndex >= 0 ? selectedIndex : 0;
});

function toggle() {
  if (props.disabled) {
    return;
  }
  open.value = !open.value;
}

function close() {
  open.value = false;
}

function select(value) {
  emit('update:modelValue', value);
  close();
}

function move(step) {
  const total = filteredOptions.value.length;
  if (!total) {
    return;
  }
  highlightedIndex.value = (highlightedIndex.value + step + total) % total;
}

function selectHighlighted() {
  const option = filteredOptions.value[highlightedIndex.value];
  if (option) {
    select(option.value);
  }
}

function optionClass(option, index) {
  if (option.value === props.modelValue) {
    return 'bg-brand-50 font-medium text-brand-700';
  }
  if (index === highlightedIndex.value) {
    return 'bg-slate-50 text-slate-900';
  }
  return 'text-slate-700 hover:bg-slate-50';
}

function onDocumentClick(event) {
  if (!rootRef.value?.contains(event.target)) {
    close();
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentClick);
});
</script>

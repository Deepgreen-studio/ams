<template>
  <div ref="rootRef" class="relative" :class="wrapperClass">
    <button
      type="button"
      class="inline-flex w-full items-center justify-between gap-2 border bg-white text-left shadow-none outline-none transition focus:outline-none focus:ring-0"
      :class="[
        sizeClasses.button,
        open
          ? 'border-brand-500 text-slate-900'
          : error
            ? 'border-rose-400 text-slate-900'
            : 'border-slate-200 text-slate-700 hover:border-slate-300',
        disabled ? 'cursor-not-allowed bg-slate-50 opacity-60' : '',
      ]"
      :disabled="disabled"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click.stop="toggle"
    >
      <span
        class="min-w-0 flex-1 truncate"
        :class="hasSelection ? 'text-slate-900' : 'text-slate-400'"
      >
        {{ selectedLabel || placeholder }}
      </span>
      <ChevronDownIcon
        class="shrink-0 text-slate-400 transition"
        :class="[sizeClasses.icon, open ? 'rotate-180' : '']"
      />
    </button>

    <div
      v-if="open"
      class="absolute left-0 z-[80] min-w-full overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200"
      :class="[
        dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5',
        'max-h-64 overflow-y-auto',
      ]"
      role="listbox"
      :aria-multiselectable="multiple || undefined"
      @click.stop
      @pointerdown.stop
    >
      <p
        v-if="!options.length"
        class="px-3.5 py-2.5 text-sm text-slate-500"
      >
        No options available
      </p>
      <button
        v-for="option in options"
        :key="String(option.value)"
        type="button"
        class="flex w-full items-center gap-2.5 px-3.5 text-left text-sm transition"
        :class="[
          sizeClasses.option,
          isSelected(option.value)
            ? 'bg-brand-50 font-medium text-brand-700'
            : 'text-slate-700 hover:bg-slate-50',
        ]"
        role="option"
        :aria-selected="isSelected(option.value)"
        @mousedown.prevent="select(option.value)"
      >
        <span
          v-if="multiple"
          class="inline-flex h-4 w-4 shrink-0 items-center justify-center rounded border"
          :class="
            isSelected(option.value)
              ? 'border-brand-600 bg-brand-600 text-white'
              : 'border-zinc-300 bg-white text-transparent'
          "
        >
          <CheckIcon class="h-3 w-3" />
        </span>
        <span class="min-w-0 flex-1 truncate">{{ option.label }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { CheckIcon, ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: {
    type: [String, Number, Array, null],
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: '',
  },
  wrapperClass: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  dropUp: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);
const rootRef = ref(null);

const sizeClasses = computed(() => {
  if (props.size === 'sm') {
    return {
      button: 'h-8 rounded-[12px] py-1.5 pl-3 pr-2.5 text-sm',
      icon: 'h-3.5 w-3.5',
      option: 'py-2',
    };
  }

  if (props.size === 'lg') {
    return {
      button: 'h-12 rounded-xl py-2.5 pl-3.5 pr-3 text-sm',
      icon: 'h-4 w-4',
      option: 'py-2.5',
    };
  }

  return {
    button: 'h-10 rounded-[12px] py-2 pl-3.5 pr-3 text-sm',
    icon: 'h-4 w-4',
    option: 'py-2.5',
  };
});

const selectedValues = computed(() => {
  if (!props.multiple) {
    return [];
  }
  return Array.isArray(props.modelValue) ? props.modelValue : [];
});

const normalizedValue = computed(() => {
  if (props.multiple) {
    return selectedValues.value;
  }
  return props.modelValue ?? '';
});

const selectedOption = computed(() =>
  props.options.find((option) => option.value === normalizedValue.value),
);

const hasSelection = computed(() => {
  if (props.multiple) {
    return selectedValues.value.length > 0;
  }
  if (normalizedValue.value === '' || normalizedValue.value === null || normalizedValue.value === undefined) {
    return Boolean(props.options.find((option) => option.value === ''));
  }
  return Boolean(selectedOption.value);
});

const selectedLabel = computed(() => {
  if (props.multiple) {
    if (!selectedValues.value.length) {
      return '';
    }
    const labels = props.options
      .filter((option) => selectedValues.value.includes(option.value))
      .map((option) => option.label);
    if (labels.length <= 2) {
      return labels.join(', ');
    }
    return `${labels.length} selected`;
  }

  if (selectedOption.value) {
    return selectedOption.value.label;
  }
  if (props.placeholder) {
    return '';
  }
  return props.options[0]?.label || '';
});

function isSelected(value) {
  if (props.multiple) {
    return selectedValues.value.includes(value);
  }
  return normalizedValue.value === value;
}

function toggle() {
  if (props.disabled) {
    return;
  }
  open.value = !open.value;
}

function select(value) {
  if (props.multiple) {
    const current = [...selectedValues.value];
    const index = current.indexOf(value);
    if (index >= 0) {
      current.splice(index, 1);
    } else {
      current.push(value);
    }
    emit('update:modelValue', current);
    emit('change', current);
    return;
  }

  emit('update:modelValue', value);
  emit('change', value);
  open.value = false;
}

function onDocumentPointerDown(event) {
  if (!open.value || !rootRef.value) {
    return;
  }
  if (!rootRef.value.contains(event.target)) {
    open.value = false;
  }
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown);
});

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown);
});
</script>

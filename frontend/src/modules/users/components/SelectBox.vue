<template>
  <div class="relative" :class="wrapperClass" @click.stop>
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
      @click="toggle"
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
      class="absolute left-0 z-30 min-w-full overflow-hidden rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200"
      :class="[
        dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5',
        size === 'lg' ? 'max-h-64 overflow-y-auto' : '',
      ]"
      role="listbox"
    >
      <button
        v-for="option in options"
        :key="String(option.value)"
        type="button"
        class="flex w-full items-center px-3.5 text-left text-sm transition"
        :class="[
          sizeClasses.option,
          option.value === modelValue
            ? 'bg-brand-50 font-medium text-brand-700'
            : 'text-slate-700 hover:bg-slate-50',
        ]"
        role="option"
        :aria-selected="option.value === modelValue"
        @click="select(option.value)"
      >
        {{ option.label }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  modelValue: {
    type: [String, Number],
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
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);

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

const selectedOption = computed(() =>
  props.options.find((option) => option.value === props.modelValue)
);

const hasSelection = computed(() => Boolean(selectedOption.value));

const selectedLabel = computed(() => {
  if (selectedOption.value) {
    return selectedOption.value.label;
  }
  if (props.placeholder) {
    return '';
  }
  return props.options[0]?.label || '';
});

function toggle() {
  if (props.disabled) {
    return;
  }
  open.value = !open.value;
}

function select(value) {
  emit('update:modelValue', value);
  emit('change', value);
  open.value = false;
}

function onDocumentClick() {
  open.value = false;
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>

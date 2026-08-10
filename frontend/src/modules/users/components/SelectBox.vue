<template>
  <div class="relative" :class="wrapperClass" @click.stop>
    <button
      type="button"
      class="inline-flex w-full items-center justify-between gap-2 rounded-[12px] border bg-white text-left shadow-none transition focus:outline-none focus:ring-0"
      :class="[
        size === 'sm' ? 'h-8 py-1.5 pl-3 pr-2.5 text-sm' : 'h-10 py-2 pl-3.5 pr-3 text-sm',
        open
          ? 'border-brand-500 text-slate-800'
          : 'border-zinc-200 text-slate-700 hover:border-zinc-300',
      ]"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click="toggle"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <ChevronDownIcon
        class="shrink-0 text-slate-400 transition"
        :class="[size === 'sm' ? 'h-3.5 w-3.5' : 'h-4 w-4', open ? 'rotate-180' : '']"
      />
    </button>

    <div
      v-if="open"
      class="absolute left-0 z-30 min-w-full overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
      :class="dropUp ? 'bottom-full mb-1' : 'top-full mt-1'"
      role="listbox"
    >
      <button
        v-for="option in options"
        :key="String(option.value)"
        type="button"
        class="flex w-full items-center px-3.5 text-left text-sm transition"
        :class="[
          size === 'sm' ? 'py-2' : 'py-2.5',
          option.value === modelValue
            ? 'bg-brand-50 font-medium text-brand-700'
            : 'text-slate-700 hover:bg-zinc-50',
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
  wrapperClass: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md'].includes(value),
  },
  dropUp: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const open = ref(false);

const selectedLabel = computed(() => {
  const match = props.options.find((option) => option.value === props.modelValue);
  return match?.label || props.options[0]?.label || '';
});

function toggle() {
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

<template>
  <div class="flex flex-col items-center justify-between gap-3 sm:flex-row">
    <p class="text-sm text-slate-500">
      Total
      <span class="font-medium text-slate-700">{{ meta?.total || 0 }}</span>
    </p>

    <div class="flex items-center gap-2">
      <SelectBox
        size="sm"
        drop-up
        wrapper-class="min-w-[7.5rem]"
        :model-value="meta?.per_page || 10"
        :options="perPageOptions"
        @change="onPerPageChange"
      />

      <button
        type="button"
        class="inline-flex h-8 w-8 items-center justify-center rounded-[12px] p-1.5 text-slate-500 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="!meta || meta.current_page <= 1 || loading"
        aria-label="Previous page"
        @click="$emit('change', meta.current_page - 1)"
      >
        <ChevronLeftIcon class="h-4 w-4" />
      </button>

      <button
        v-for="page in visiblePages"
        :key="page"
        type="button"
        class="inline-flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-sm font-medium transition"
        :class="
          page === (meta?.current_page || 1)
            ? 'bg-brand-600 text-white'
            : 'text-slate-600 hover:bg-zinc-100'
        "
        :disabled="loading || page === '...'"
        @click="page !== '...' && $emit('change', page)"
      >
        {{ page }}
      </button>

      <button
        type="button"
        class="inline-flex h-8 w-8 items-center justify-center rounded-[12px] p-1.5 text-slate-500 transition hover:bg-zinc-100 disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="!meta || meta.current_page >= meta.last_page || loading"
        aria-label="Next page"
        @click="$emit('change', meta.current_page + 1)"
      >
        <ChevronRightIcon class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  meta: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['change', 'per-page']);

const perPageOptions = [
  { value: 10, label: '10 / page' },
  { value: 25, label: '25 / page' },
  { value: 50, label: '50 / page' },
  { value: 100, label: '100 / page' },
];

const visiblePages = computed(() => {
  const current = props.meta?.current_page || 1;
  const last = props.meta?.last_page || 1;

  if (last <= 5) {
    return Array.from({ length: last }, (_, index) => index + 1);
  }

  const pages = new Set([1, last, current, current - 1, current + 1]);
  const sorted = [...pages].filter((page) => page >= 1 && page <= last).sort((a, b) => a - b);

  const result = [];
  sorted.forEach((page, index) => {
    if (index > 0 && page - sorted[index - 1] > 1) {
      result.push('...');
    }
    result.push(page);
  });

  return result;
});

function onPerPageChange(value) {
  emit('per-page', Number(value));
}
</script>

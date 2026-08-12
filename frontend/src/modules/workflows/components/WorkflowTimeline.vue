<template>
  <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
    <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
      <h3 class="text-base font-semibold text-slate-900">Workflow timeline</h3>
      <p class="mt-1 text-sm text-slate-500">
        Stage transitions, approvals, escalations, and decisions.
      </p>
    </div>

    <div v-if="loading" class="space-y-3 px-6 py-6 sm:px-8">
      <div v-for="n in 4" :key="n" class="h-16 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else-if="!logs.length" class="px-6 py-12 text-center text-sm text-slate-500 sm:px-8">
      No timeline events yet.
    </div>

    <template v-else>
      <ol class="space-y-0 px-6 py-5 sm:px-8 sm:py-6">
        <li
          v-for="(item, index) in pagedLogs"
          :key="item.uuid"
          class="flex gap-3.5"
        >
          <div class="relative flex w-8 shrink-0 flex-col items-center">
            <span
              class="z-10 mt-1.5 h-3 w-3 rounded-full ring-4 ring-white"
              :class="dotClass(item.action)"
            />
            <span
              v-if="index < pagedLogs.length - 1"
              class="absolute top-4 h-[calc(100%-0.25rem)] w-px bg-zinc-200"
            />
          </div>

          <div class="min-w-0 flex-1 pb-6 last:pb-0">
            <p class="text-sm font-semibold text-slate-900">
              {{ item.action_label || item.action }}
              <span v-if="item.step?.name" class="font-normal text-slate-500">
                · {{ item.step.name }}
              </span>
            </p>
            <p class="mt-1 text-xs text-slate-500">
              {{ formatDate(item.created_at) }}
              <span v-if="item.actor?.full_name"> · {{ item.actor.full_name }}</span>
              <span v-if="item.from_status || item.to_status">
                · {{ item.from_status || '—' }} → {{ item.to_status || '—' }}
              </span>
            </p>
            <p
              v-if="item.comment"
              class="mt-2.5 whitespace-pre-wrap rounded-[12px] bg-zinc-50 px-3.5 py-2.5 text-sm text-slate-700 ring-1 ring-zinc-100"
            >
              {{ item.comment }}
            </p>
          </div>
        </li>
      </ol>

      <div
        v-if="meta.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination
          :meta="meta"
          :loading="loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Pagination from '@/modules/users/components/Pagination.vue';

const props = defineProps({
  logs: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const page = ref(1);
const perPage = ref(10);

watch(
  () => props.logs.length,
  () => {
    page.value = 1;
  },
);

const meta = computed(() => {
  const total = props.logs.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage.value));
  return {
    total,
    per_page: perPage.value,
    current_page: Math.min(page.value, lastPage),
    last_page: lastPage,
  };
});

const pagedLogs = computed(() => {
  const start = (meta.value.current_page - 1) * perPage.value;
  return props.logs.slice(start, start + perPage.value);
});

function onPageChange(nextPage) {
  page.value = nextPage;
}

function onPerPageChange(nextPerPage) {
  perPage.value = nextPerPage;
  page.value = 1;
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function dotClass(action) {
  if (action === 'approved' || action === 'completed') return 'bg-emerald-500';
  if (action === 'rejected' || action === 'cancelled' || action === 'timed_out') return 'bg-rose-500';
  if (action === 'escalated') return 'bg-amber-500';
  if (action === 'started') return 'bg-sky-500';
  return 'bg-brand-600';
}
</script>

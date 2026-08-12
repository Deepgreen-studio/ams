<template>
  <div>
    <AiSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2">
      <SelectBox
        v-model="days"
        wrapper-class="min-w-[10rem]"
        :options="dayOptions"
        @change="load"
      />
    </div>

    <div v-if="store.loading && !store.usageAnalytics" class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="n in 4" :key="n" class="h-28 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div v-else class="mb-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in cards"
        :key="card.label"
        class="flex items-center justify-between gap-4 rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100 transition hover:ring-brand-200"
      >
        <div class="min-w-0">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ card.value }}</p>
        </div>
        <div
          class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-[12px]"
          :class="card.iconBg"
        >
          <component :is="card.icon" class="h-5 w-5" :class="card.iconColor" />
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">By feature</h2>
        <div v-if="store.loading && !(store.usageAnalytics?.by_feature || []).length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p
          v-else-if="!(store.usageAnalytics?.by_feature || []).length"
          class="py-10 text-center text-sm text-slate-500"
        >
          No data.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="row in store.usageAnalytics?.by_feature || []"
            :key="row.feature"
            class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0 text-sm"
          >
            <span class="truncate text-slate-700">{{ row.feature }}</span>
            <span class="shrink-0 font-medium text-slate-900">{{ row.total }} req · {{ row.tokens }} tok</span>
          </li>
        </ul>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">By driver</h2>
        <div v-if="store.loading && !(store.usageAnalytics?.by_driver || []).length" class="space-y-3">
          <div v-for="n in 4" :key="n" class="h-10 animate-pulse rounded-[12px] bg-zinc-100" />
        </div>
        <p
          v-else-if="!(store.usageAnalytics?.by_driver || []).length"
          class="py-10 text-center text-sm text-slate-500"
        >
          No data.
        </p>
        <ul v-else class="divide-y divide-zinc-100">
          <li
            v-for="row in store.usageAnalytics?.by_driver || []"
            :key="row.driver || 'none'"
            class="flex items-center justify-between gap-3 py-3.5 first:pt-0 last:pb-0 text-sm"
          >
            <span class="truncate text-slate-700">{{ row.driver || 'n/a' }}</span>
            <span class="shrink-0 font-medium text-slate-900">{{ row.total }} req · {{ row.tokens }} tok</span>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import {
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  BoltIcon,
} from '@heroicons/vue/24/outline';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const days = ref(30);

const dayOptions = [
  { value: 7, label: 'Last 7 days' },
  { value: 30, label: 'Last 30 days' },
  { value: 90, label: 'Last 90 days' },
];

const cards = computed(() => [
  {
    label: 'Requests',
    value: store.usageAnalytics?.requests ?? 0,
    icon: BoltIcon,
    iconBg: 'bg-brand-50',
    iconColor: 'text-brand-500',
  },
  {
    label: 'Tokens in',
    value: store.usageAnalytics?.tokens_in ?? 0,
    icon: ArrowDownTrayIcon,
    iconBg: 'bg-sky-50',
    iconColor: 'text-sky-500',
  },
  {
    label: 'Tokens out',
    value: store.usageAnalytics?.tokens_out ?? 0,
    icon: ArrowUpTrayIcon,
    iconBg: 'bg-violet-50',
    iconColor: 'text-violet-500',
  },
  {
    label: 'Avg latency (ms)',
    value: store.usageAnalytics?.avg_latency_ms ?? 0,
    icon: ArrowPathIcon,
    iconBg: 'bg-amber-50',
    iconColor: 'text-amber-500',
  },
]);

async function load() {
  await store.fetchAnalytics({ days: days.value });
}

onMounted(load);
</script>

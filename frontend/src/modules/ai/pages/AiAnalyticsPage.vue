<template>
  <div>
    <PageHeader
      title="Usage Analytics"
      description="Token usage, request volume, and feature breakdown across AI providers."
    />
    <AiSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-4">
      <select v-model="days" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option :value="7">Last 7 days</option>
        <option :value="30">Last 30 days</option>
        <option :value="90">Last 90 days</option>
      </select>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="card in cards" :key="card.label" class="rounded-xl border border-slate-200 bg-white px-4 py-3">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">By feature</h2>
        <ul class="divide-y divide-slate-100">
          <li v-if="!(store.usageAnalytics?.by_feature || []).length" class="py-6 text-center text-sm text-slate-500">No data.</li>
          <li
            v-for="row in store.usageAnalytics?.by_feature || []"
            :key="row.feature"
            class="flex items-center justify-between py-3 text-sm"
          >
            <span class="text-slate-700">{{ row.feature }}</span>
            <span class="font-medium text-slate-900">{{ row.total }} req · {{ row.tokens }} tok</span>
          </li>
        </ul>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">By driver</h2>
        <ul class="divide-y divide-slate-100">
          <li v-if="!(store.usageAnalytics?.by_driver || []).length" class="py-6 text-center text-sm text-slate-500">No data.</li>
          <li
            v-for="row in store.usageAnalytics?.by_driver || []"
            :key="row.driver || 'none'"
            class="flex items-center justify-between py-3 text-sm"
          >
            <span class="text-slate-700">{{ row.driver || 'n/a' }}</span>
            <span class="font-medium text-slate-900">{{ row.total }} req · {{ row.tokens }} tok</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const days = ref(30);

const cards = computed(() => [
  { label: 'Requests', value: store.usageAnalytics?.requests ?? 0 },
  { label: 'Tokens in', value: store.usageAnalytics?.tokens_in ?? 0 },
  { label: 'Tokens out', value: store.usageAnalytics?.tokens_out ?? 0 },
  { label: 'Avg latency (ms)', value: store.usageAnalytics?.avg_latency_ms ?? 0 },
]);

async function load() {
  await store.fetchAnalytics({ days: days.value });
}

onMounted(load);
</script>

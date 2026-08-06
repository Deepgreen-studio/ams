<template>
  <div>
    <PageHeader title="SLA Policies" description="Global defaults and company overrides" />
    <SupportSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Policy</th>
            <th class="px-4 py-3">Scope</th>
            <th class="px-4 py-3">Priority</th>
            <th class="px-4 py-3">Response</th>
            <th class="px-4 py-3">Resolution</th>
            <th class="px-4 py-3">Rules</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="policy in store.policies" :key="policy.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ policy.name }}</p>
              <p class="text-xs text-slate-500">{{ policy.code || '—' }}</p>
            </td>
            <td class="px-4 py-3">
              {{ policy.company?.company_name || 'Global' }}
              <span v-if="policy.is_default" class="ml-1 text-xs text-brand-700">(default)</span>
            </td>
            <td class="px-4 py-3">{{ policy.priority_label || 'Any' }}</td>
            <td class="px-4 py-3">{{ policy.response_target_minutes }}m</td>
            <td class="px-4 py-3">{{ policy.resolution_target_minutes }}m</td>
            <td class="px-4 py-3">{{ policy.escalation_rules?.length || 0 }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import SupportSubnav from '@/modules/support/components/SupportSubnav.vue';
import { useSupportSlaStore } from '@/modules/support/stores/supportSla';

const store = useSupportSlaStore();

onMounted(() => store.fetchPolicies({ per_page: 50 }));
</script>

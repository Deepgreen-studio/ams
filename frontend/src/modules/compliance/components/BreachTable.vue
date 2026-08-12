<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div v-if="loading" class="space-y-3 p-6">
      <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
    </div>
    <EmptyState
      v-else-if="!breaches.length"
      title="No data breaches found"
      description="Report an incident to begin breach management."
    >
      <template #action><slot name="empty-action" /></template>
    </EmptyState>
    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Incident</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Type</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Severity</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Affected</th>
            <th
              v-if="hasAnyAction"
              class="px-4 py-3 text-right font-semibold text-slate-600"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in breaches" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.breach_number }}</p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.breach_type_label || item.breach_type }}
            </td>
            <td class="px-4 py-3">
              <BreachStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="px-4 py-3">
              <BreachSeverityBadge :severity="item.severity" :label="item.severity_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.affected_user_count ?? 0 }}
            </td>
            <td v-if="hasAnyAction" class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  v-if="can('compliance.view')"
                  :to="{ name: 'compliance.breaches.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                >
                  View
                </RouterLink>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import { usePermissions } from '@/composables/usePermissions';
import BreachStatusBadge from '@/modules/compliance/components/BreachStatusBadge.vue';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';

defineProps({
  breaches: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
});

const { can, canAny } = usePermissions();
const hasAnyAction = computed(() => canAny('compliance.view'));
</script>

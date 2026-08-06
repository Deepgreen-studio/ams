<template>
  <div>
    <PageHeader title="DPIA risk matrix" description="Likelihood × impact matrix for active risk register entries." />
    <ComplianceSubnav />

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-5">
      <table class="min-w-[640px] border-collapse text-sm">
        <thead>
          <tr>
            <th class="p-2 text-left text-slate-500" />
            <th v-for="impact in impacts" :key="impact" class="p-2 text-center text-slate-600">{{ impact }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="likelihood in likelihoods" :key="likelihood">
            <th class="p-2 text-left text-slate-600">{{ likelihood }}</th>
            <td
              v-for="impact in impacts"
              :key="`${likelihood}-${impact}`"
              class="border border-slate-200 p-2 text-center"
              :class="cellTone(likelihood, impact)"
            >
              <p class="font-semibold">{{ cell(likelihood, impact)?.count || 0 }}</p>
              <p class="text-[10px] uppercase opacity-80">{{ cell(likelihood, impact)?.level || '—' }}</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const store = useDpiaStore();
const likelihoods = [5, 4, 3, 2, 1];
const impacts = [1, 2, 3, 4, 5];

const cellMap = computed(() => {
  const map = {};
  for (const item of store.riskMatrix?.cells || []) {
    map[`${item.likelihood}-${item.impact}`] = item;
  }
  return map;
});

onMounted(() => store.fetchRiskMatrix());

function cell(likelihood, impact) {
  return cellMap.value[`${likelihood}-${impact}`];
}

function cellTone(likelihood, impact) {
  const level = cell(likelihood, impact)?.level;
  return {
    'bg-emerald-50 text-emerald-800': level === 'low',
    'bg-amber-50 text-amber-800': level === 'medium',
    'bg-orange-50 text-orange-800': level === 'high',
    'bg-rose-50 text-rose-800': level === 'critical',
    'bg-slate-50 text-slate-600': !level,
  };
}
</script>

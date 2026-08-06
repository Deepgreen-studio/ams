<template>
  <div>
    <PageHeader
      title="Mitigation tracker"
      description="Track open risks, mitigation plans, and action completion."
    >
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="showForm = !showForm"
        >
          {{ showForm ? 'Hide form' : 'Register risk' }}
        </button>
      </template>
    </PageHeader>
    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>
    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>

    <div v-if="showForm" class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="onCreate">
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
          <select v-model="form.company_id" class="input" required>
            <option value="" disabled>Select company</option>
            <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
              {{ company.company_name }}
            </option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
          <input v-model="form.title" type="text" class="input" required />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Category</label>
          <select v-model="form.category" class="input">
            <option value="privacy">Privacy</option>
            <option value="security">Security</option>
            <option value="operational">Operational</option>
            <option value="legal">Legal</option>
            <option value="third_party">Third party</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Likelihood</label>
            <input v-model.number="form.likelihood" type="number" min="1" max="5" class="input" />
          </div>
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Impact</label>
            <input v-model.number="form.impact" type="number" min="1" max="5" class="input" />
          </div>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Mitigation plan</label>
          <textarea v-model="form.mitigation_plan" rows="3" class="input" />
        </div>
        <div class="md:col-span-2 flex justify-end">
          <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white" :disabled="store.saving">
            Save risk
          </button>
        </div>
      </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <EmptyState
        v-if="!store.risks.length && !store.loading"
        title="No open mitigations"
        description="Register a risk to begin tracking mitigation actions."
      />
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Risk</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Score</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.risks" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.risk_number }}</p>
            </td>
            <td class="px-4 py-3">
              <DpiaStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.risk_score ?? '—' }} / {{ item.risk_level || '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <button
                type="button"
                class="text-xs font-medium text-brand-700 hover:underline"
                @click="openActions(item)"
              >
                Add action
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="selectedRisk" class="mt-4 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-3 text-sm font-semibold text-slate-900">
        Mitigation action for {{ selectedRisk.risk_number }}
      </h2>
      <form class="space-y-3" @submit.prevent="onAddAction">
        <input v-model="actionForm.title" type="text" class="input" placeholder="Action title" required />
        <textarea v-model="actionForm.description" rows="2" class="input" placeholder="Description" />
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="selectedRisk = null">
            Cancel
          </button>
          <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white" :disabled="store.saving">
            Save action
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { companyService } from '@/modules/companies/services/companyService';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';

const store = useDpiaStore();
const companies = ref([]);
const showForm = ref(false);
const selectedRisk = ref(null);

const form = reactive({
  company_id: '',
  title: '',
  category: 'privacy',
  likelihood: 3,
  impact: 3,
  mitigation_plan: '',
});

const actionForm = reactive({
  title: '',
  description: '',
  action_type: 'mitigation',
});

onMounted(async () => {
  await store.fetchMitigation({ per_page: 20, page: 1 });
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

async function onCreate() {
  await store.createRisk({ ...form });
  showForm.value = false;
  form.title = '';
  form.mitigation_plan = '';
  await store.fetchMitigation({ per_page: 20, page: 1 });
}

function openActions(risk) {
  selectedRisk.value = risk;
  actionForm.title = '';
  actionForm.description = '';
}

async function onAddAction() {
  await store.addRiskAction(selectedRisk.value.uuid, { ...actionForm });
  selectedRisk.value = null;
  await store.fetchMitigation({ per_page: 20, page: 1 });
}
</script>

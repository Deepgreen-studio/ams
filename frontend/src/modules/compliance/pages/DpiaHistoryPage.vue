<template>
  <div>
    <PageHeader title="Assessment history" description="All DPIA assessments across templates and statuses.">
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.dpia.wizard' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New DPIA
        </RouterLink>
      </template>
    </PageHeader>
    <ComplianceSubnav />

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
      <form class="flex flex-col gap-3 lg:flex-row" @submit.prevent="onSearch">
        <input
          v-model="local.search"
          type="search"
          placeholder="Search assessments..."
          class="h-11 flex-1 rounded-[12px] border border-slate-300 px-3 text-sm"
        />
        <select v-model="local.status" class="h-11 rounded-[12px] border border-slate-300 px-3 text-sm">
          <option value="">All statuses</option>
          <option value="draft">Draft</option>
          <option value="in_progress">In progress</option>
          <option value="pending_review">Pending review</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="archived">Archived</option>
        </select>
        <button type="submit" class="h-11 rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white">
          Search
        </button>
      </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.assessments.length"
        title="No assessments"
        description="Create a DPIA using the wizard."
      />
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Assessment</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 md:table-cell">Template</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="hidden px-4 py-3 text-left font-semibold text-slate-600 lg:table-cell">Risk</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.assessments" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.assessment_number }}</p>
            </td>
            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">
              {{ item.template_label || item.template_code }}
            </td>
            <td class="px-4 py-3">
              <DpiaStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="hidden px-4 py-3 text-slate-600 lg:table-cell">
              {{ item.overall_risk_score ?? '—' }}
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'compliance.dpia.show', params: { id: item.uuid } }"
                class="text-xs font-medium text-brand-700 hover:underline"
              >
                View
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <Pagination
        :meta="store.meta"
        :loading="store.loading"
        @change="(page) => store.fetchAssessments({ ...local, page })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import DpiaStatusBadge from '@/modules/compliance/components/DpiaStatusBadge.vue';
import { useDpiaStore } from '@/modules/compliance/stores/dpia';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = useDpiaStore();
const local = reactive({ search: '', status: '', page: 1 });

onMounted(() => store.fetchAssessments(local));

function onSearch() {
  local.page = 1;
  store.fetchAssessments(local);
}
</script>

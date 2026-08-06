<template>
  <div>
    <PageHeader
      title="Policy documents"
      description="Search privacy, terms, cookie, security, and internal governance documents."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.policies.dashboard' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.policies.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New policy
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4"
      @submit.prevent="onFilter"
    >
      <input
        v-model="local.search"
        type="search"
        class="input md:col-span-2"
        placeholder="Search title or policy number"
      />
      <select v-model="local.status" class="input">
        <option value="">All statuses</option>
        <option value="draft">Draft</option>
        <option value="review">Review</option>
        <option value="approved">Approved</option>
        <option value="published">Published</option>
        <option value="archived">Archived</option>
      </select>
      <select v-model="local.policy_type" class="input">
        <option value="">All types</option>
        <option value="privacy_policy">Privacy Policy</option>
        <option value="terms">Terms & Conditions</option>
        <option value="cookie_policy">Cookie Policy</option>
        <option value="security_policy">Security Policy</option>
        <option value="internal_policy">Internal Policy</option>
        <option value="employee_handbook">Employee Handbook</option>
        <option value="compliance_document">Compliance Document</option>
      </select>
      <div class="flex gap-2 md:col-span-4">
        <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white">
          Filter
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
          @click="onReset"
        >
          Reset
        </button>
      </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.policies.length"
        title="No policies found"
        description="Create a policy document to start governance."
      >
        <template #action>
          <RouterLink
            :to="{ name: 'compliance.policies.create' }"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          >
            New policy
          </RouterLink>
        </template>
      </EmptyState>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Policy</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Type</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Version</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.policies" :key="item.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.title }}</p>
              <p class="text-xs text-slate-500">{{ item.policy_number }}</p>
            </td>
            <td class="px-4 py-3 text-slate-600">{{ item.policy_type_label }}</td>
            <td class="px-4 py-3 text-slate-600">v{{ item.current_version }}</td>
            <td class="px-4 py-3">
              <PolicyStatusBadge :status="item.status" :label="item.status_label" />
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'compliance.policies.show', params: { id: item.uuid } }"
                class="font-medium text-brand-700 hover:underline"
              >
                Open
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
        @change="(page) => store.fetchPolicies({ page })"
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
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';
import Pagination from '@/modules/users/components/Pagination.vue';

const store = usePolicyStore();
const local = reactive({
  search: '',
  status: '',
  policy_type: '',
});

onMounted(() => store.fetchPolicies());

function onFilter() {
  store.fetchPolicies({ ...local, page: 1 });
}

function onReset() {
  local.search = '';
  local.status = '';
  local.policy_type = '';
  store.fetchPolicies({
    search: '',
    status: '',
    policy_type: '',
    page: 1,
  });
}
</script>

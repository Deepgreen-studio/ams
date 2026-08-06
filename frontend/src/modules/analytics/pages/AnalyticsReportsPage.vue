<template>
  <div>
    <PageHeader title="Report Builder" description="Design tabular, chart, grouped, and scheduled enterprise reports.">
      <template #actions>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="showCreate = true"
        >
          New report
        </button>
      </template>
    </PageHeader>

    <AnalyticsSubnav />

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4">
      <label class="text-sm text-slate-600">
        Search
        <input v-model="local.search" type="search" class="mt-1 block w-48 rounded-lg border border-slate-200 px-3 py-2 text-sm" />
      </label>
      <label class="text-sm text-slate-600">
        Type
        <select v-model="local.report_type" class="mt-1 block w-40 rounded-lg border border-slate-200 px-3 py-2 text-sm">
          <option value="">All</option>
          <option v-for="t in store.reportTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
        </select>
      </label>
      <label class="text-sm text-slate-600">
        Scheduled
        <select v-model="local.is_scheduled" class="mt-1 block w-32 rounded-lg border border-slate-200 px-3 py-2 text-sm">
          <option value="">All</option>
          <option value="1">Yes</option>
          <option value="0">No</option>
        </select>
      </label>
      <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white" @click="onApply">Apply</button>
    </div>

    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>
    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">Name</th>
            <th class="px-4 py-3 font-medium">Type</th>
            <th class="px-4 py-3 font-medium">Status</th>
            <th class="px-4 py-3 font-medium">Runs</th>
            <th class="px-4 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading reports…</td>
          </tr>
          <tr v-else-if="!store.reports.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No reports found.</td>
          </tr>
          <tr v-for="item in store.reports" :key="item.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.description || item.slug }}</p>
            </td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.report_type }}</td>
            <td class="px-4 py-3">
              <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-medium capitalize text-slate-700">{{ item.status }}</span>
              <span v-if="item.is_scheduled" class="ml-2 text-xs text-amber-600">scheduled</span>
              <span v-if="item.is_saved" class="ml-2 text-xs text-emerald-600">saved</span>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ item.runs_count ?? 0 }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-2">
                <RouterLink
                  :to="{ name: 'analytics.reports.designer', params: { uuid: item.uuid } }"
                  class="text-sm font-medium text-brand-700 hover:underline"
                >
                  Design
                </RouterLink>
                <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="onDelete(item)">
                  Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="showCreate" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/40 p-4">
      <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-slate-900">Create report</h3>
        <div class="mt-4 space-y-3">
          <label class="block text-sm text-slate-600">
            Name
            <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
          </label>
          <label class="block text-sm text-slate-600">
            Type
            <select v-model="form.report_type" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm">
              <option v-for="t in store.reportTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
          </label>
          <label class="block text-sm text-slate-600">
            Description
            <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm" />
          </label>
        </div>
        <div class="mt-6 flex justify-end gap-2">
          <button type="button" class="rounded-lg px-3 py-2 text-sm text-slate-600" @click="showCreate = false">Cancel</button>
          <button
            type="button"
            class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
            :disabled="store.saving || !form.name"
            @click="onCreate"
          >
            Create
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import AnalyticsSubnav from '@/modules/analytics/components/AnalyticsSubnav.vue';
import { useEnterpriseAnalyticsStore } from '@/modules/analytics/stores/enterpriseAnalytics';

const store = useEnterpriseAnalyticsStore();
const router = useRouter();
const showCreate = ref(false);
const local = reactive({ search: '', report_type: '', is_scheduled: '' });
const form = reactive({
  name: '',
  description: '',
  report_type: 'tabular',
  status: 'draft',
  is_saved: true,
  visibility: 'personal',
});

async function onApply() {
  await store.fetchReports({ ...local, page: 1 });
}

async function onCreate() {
  const report = await store.createReport({ ...form });
  showCreate.value = false;
  form.name = '';
  form.description = '';
  if (report?.uuid) {
    await router.push({ name: 'analytics.reports.designer', params: { uuid: report.uuid } });
  }
}

async function onDelete(item) {
  if (!confirm(`Delete report “${item.name}”?`)) return;
  await store.deleteReport(item.uuid);
}

onMounted(async () => {
  await store.fetchReports();
});
</script>

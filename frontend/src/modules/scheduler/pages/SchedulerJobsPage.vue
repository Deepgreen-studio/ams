<template>
  <div>
    <!-- <PageHeader title="Scheduled Jobs" description="Manage cron, recurring, one-time, delayed, and queue jobs.">
      <template #actions>
        <RouterLink
          :to="{ name: 'scheduler.jobs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create job
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'scheduler.jobs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create job
        </RouterLink>
    </Teleport>

    <SchedulerSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search jobs..."
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <select v-model="filters.job_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All types</option>
        <option v-for="item in store.catalog.job_types" :key="item.value" :value="item.value">{{ item.label }}</option>
      </select>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium hover:bg-slate-50" @click="load">
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Job</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3">Schedule</th>
            <th class="px-4 py-3">Next run</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">Loading...</td>
          </tr>
          <tr v-else-if="!store.jobs.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">No scheduled jobs.</td>
          </tr>
          <tr v-for="job in store.jobs" :key="job.uuid">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ job.name }}</p>
              <p class="text-xs text-slate-500">{{ job.handler_key }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ job.job_type_label || job.job_type }}</td>
            <td class="px-4 py-3 text-slate-600">
              <span v-if="job.schedule_cron">{{ job.schedule_cron }}</span>
              <span v-else-if="job.delay_minutes">+{{ job.delay_minutes }}m</span>
              <span v-else-if="job.run_at">{{ formatDate(job.run_at) }}</span>
              <span v-else>—</span>
            </td>
            <td class="px-4 py-3 text-slate-500">{{ formatDate(job.next_run_at) }}</td>
            <td class="px-4 py-3">
              <button
                type="button"
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="job.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                @click="store.toggleJob(job.uuid, !job.is_enabled)"
              >
                {{ job.is_enabled ? 'Enabled' : 'Disabled' }}
              </button>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <button type="button" class="text-sm font-medium text-slate-700 hover:underline" :disabled="store.saving" @click="run(job)">
                Run
              </button>
              <RouterLink
                :to="{ name: 'scheduler.jobs.edit', params: { id: job.uuid } }"
                class="text-sm font-medium text-brand-700 hover:underline"
              >
                Edit
              </RouterLink>
              <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="remove(job)">
                Delete
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const filters = reactive({ search: '', job_type: '' });

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function load() {
  await store.fetchJobs({ ...filters });
}

async function run(job) {
  await store.runJob(job.uuid);
  await load();
}

async function remove(job) {
  if (!window.confirm(`Delete job "${job.name}"?`)) return;
  await store.deleteJob(job.uuid);
}

onMounted(load);
</script>

<template>
  <div>
    <PageHeader
      :title="isEdit ? 'Edit Scheduled Job' : 'Create Scheduled Job'"
      description="Configure handler, schedule type, cron/delay, and queue options."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'scheduler.jobs' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
      </template>
    </PageHeader>

    <SchedulerSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block font-medium text-slate-700">Name</span>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Job type</span>
            <select v-model="form.job_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option v-for="item in store.catalog.job_types" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Handler</span>
            <select v-model="form.handler_key" required class="w-full rounded-lg border border-slate-300 px-3 py-2" @change="onHandlerChange">
              <option v-for="item in store.catalog.handlers" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </label>
          <label v-if="needsCron" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Cron expression</span>
            <input v-model="form.schedule_cron" class="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="0 6 * * *" />
          </label>
          <label v-if="form.job_type === 'one_time'" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Run at</span>
            <input v-model="form.run_at" type="datetime-local" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label v-if="form.job_type === 'delayed'" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Delay (minutes)</span>
            <input v-model.number="form.delay_minutes" type="number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Queue</span>
            <input v-model="form.queue_name" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block font-medium text-slate-700">Description</span>
            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
            Enabled
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.without_overlapping" type="checkbox" class="rounded border-slate-300" />
            Without overlapping
          </label>
        </div>
      </section>

      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
      >
        {{ store.saving ? 'Saving...' : isEdit ? 'Update job' : 'Create job' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const route = useRoute();
const router = useRouter();
const isEdit = computed(() => Boolean(route.params.id));
const needsCron = computed(() => ['cron', 'recurring', 'queue'].includes(form.job_type));

const form = reactive({
  name: '',
  description: '',
  job_type: 'cron',
  handler_key: 'daily_report',
  schedule_cron: '0 6 * * *',
  timezone: 'UTC',
  run_at: '',
  delay_minutes: 15,
  queue_name: 'default',
  is_enabled: true,
  without_overlapping: true,
  payload: {},
});

function onHandlerChange() {
  const handler = store.catalog.handlers.find((item) => item.value === form.handler_key);
  if (handler?.default_cron && needsCron.value) {
    form.schedule_cron = handler.default_cron;
  }
}

function hydrate(job) {
  form.name = job.name || '';
  form.description = job.description || '';
  form.job_type = job.job_type || 'cron';
  form.handler_key = job.handler_key || 'daily_report';
  form.schedule_cron = job.schedule_cron || '';
  form.timezone = job.timezone || 'UTC';
  form.run_at = job.run_at ? new Date(job.run_at).toISOString().slice(0, 16) : '';
  form.delay_minutes = job.delay_minutes || 15;
  form.queue_name = job.queue_name || 'default';
  form.is_enabled = Boolean(job.is_enabled);
  form.without_overlapping = job.without_overlapping !== false;
  form.payload = job.payload || {};
}

async function submit() {
  const payload = {
    name: form.name,
    description: form.description,
    job_type: form.job_type,
    handler_key: form.handler_key,
    schedule_cron: needsCron.value ? form.schedule_cron : null,
    timezone: form.timezone,
    run_at: form.job_type === 'one_time' && form.run_at ? new Date(form.run_at).toISOString() : null,
    delay_minutes: form.job_type === 'delayed' ? form.delay_minutes : null,
    queue_name: form.queue_name,
    is_enabled: form.is_enabled,
    without_overlapping: form.without_overlapping,
    payload: form.payload,
  };

  const saved = await store.saveJob(payload, isEdit.value ? route.params.id : null);
  if (!isEdit.value && saved?.uuid) {
    await router.push({ name: 'scheduler.jobs.edit', params: { id: saved.uuid } });
  }
}

onMounted(async () => {
  await store.fetchCatalog();
  if (isEdit.value) {
    hydrate(await store.fetchJob(route.params.id));
  }
});
</script>

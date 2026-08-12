<template>
  <div>
    <!-- <PageHeader
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
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'scheduler.jobs' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
    </Teleport>

    <SchedulerSubnav />

    <form class="max-w-3xl space-y-6" novalidate @submit.prevent="submit">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm md:col-span-2">
            <span class="mb-1.5 block font-medium text-slate-700">Name</span>
            <input
              v-model="form.name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('name')"
            />
            <p v-if="fieldErrors.name" class="mt-1 text-xs text-rose-600">{{ fieldErrors.name[0] }}</p>
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Job type</span>
            <select
              v-model="form.job_type"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('job_type')"
            >
              <option v-for="item in store.catalog.job_types" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
            <p v-if="fieldErrors.job_type" class="mt-1 text-xs text-rose-600">{{ fieldErrors.job_type[0] }}</p>
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Handler</span>
            <select
              v-model="form.handler_key"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('handler_key')"
              @change="onHandlerChange"
            >
              <option v-for="item in store.catalog.handlers" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
            <p v-if="fieldErrors.handler_key" class="mt-1 text-xs text-rose-600">{{ fieldErrors.handler_key[0] }}</p>
          </label>
          <label v-if="needsCron" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Cron expression</span>
            <input
              v-model="form.schedule_cron"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 font-mono text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('schedule_cron')"
              placeholder="0 6 * * *"
            />
            <p v-if="fieldErrors.schedule_cron" class="mt-1 text-xs text-rose-600">
              {{ fieldErrors.schedule_cron[0] }}
            </p>
          </label>
          <label v-if="form.job_type === 'one_time'" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Run at</span>
            <input
              v-model="form.run_at"
              type="datetime-local"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('run_at')"
            />
            <p v-if="fieldErrors.run_at" class="mt-1 text-xs text-rose-600">{{ fieldErrors.run_at[0] }}</p>
          </label>
          <label v-if="form.job_type === 'delayed'" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Delay (minutes)</span>
            <input
              v-model.number="form.delay_minutes"
              type="number"
              min="1"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('delay_minutes')"
            />
            <p v-if="fieldErrors.delay_minutes" class="mt-1 text-xs text-rose-600">
              {{ fieldErrors.delay_minutes[0] }}
            </p>
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Queue</span>
            <input
              v-model="form.queue_name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1.5 block font-medium text-slate-700">Description</span>
            <textarea
              v-model="form.description"
              rows="2"
              class="w-full rounded-[12px] border border-zinc-200 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-zinc-300" />
            Enabled
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.without_overlapping" type="checkbox" class="rounded border-zinc-300" />
            Without overlapping
          </label>
        </div>
      </section>

      <button
        type="submit"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="store.saving"
      >
        {{ store.saving ? 'Saving...' : isEdit ? 'Update job' : 'Create job' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { useToast } from '@/composables/useToast';
import SchedulerSubnav from '@/modules/scheduler/components/SchedulerSubnav.vue';
import { useSchedulerStore } from '@/modules/scheduler/stores/scheduler';

const store = useSchedulerStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const fieldErrors = ref({});
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

watch(
  () => store.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

function fieldClass(field) {
  return fieldErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

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
  fieldErrors.value = {};
}

function validate() {
  const next = {};

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.job_type || '').trim()) {
    next.job_type = ['The job type field is required.'];
  }

  if (!String(form.handler_key || '').trim()) {
    next.handler_key = ['The handler field is required.'];
  }

  if (needsCron.value && !String(form.schedule_cron || '').trim()) {
    next.schedule_cron = ['The cron expression field is required.'];
  }

  if (form.job_type === 'one_time' && !String(form.run_at || '').trim()) {
    next.run_at = ['Please choose a run time.'];
  }

  if (form.job_type === 'delayed') {
    const delay = Number(form.delay_minutes);
    if (!Number.isInteger(delay) || delay < 1) {
      next.delay_minutes = ['Delay must be at least 1 minute.'];
    }
  }

  fieldErrors.value = next;
  return Object.keys(next).length === 0;
}

async function submit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  fieldErrors.value = {};

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

  try {
    const saved = await store.saveJob(payload, isEdit.value ? route.params.id : null);
    if (!isEdit.value && saved?.uuid) {
      await router.push({ name: 'scheduler.jobs.edit', params: { id: saved.uuid } });
    }
  } catch {
    // Store sets error; toast watch handles display.
  }
}

onMounted(async () => {
  await store.fetchCatalog();
  if (isEdit.value) {
    hydrate(await store.fetchJob(route.params.id));
  }
});
</script>

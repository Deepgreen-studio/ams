<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'automation.rules' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to rules
      </RouterLink>
    </Teleport>

    <AutomationSubnav />

    <form class="space-y-4" novalidate @submit.prevent="submit">
      <!-- 1. Trigger -->
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-5">
          <h2 class="text-base font-semibold text-slate-900">1. Trigger</h2>
          <p class="mt-0.5 text-sm text-slate-500">Define when this automation should start.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm md:col-span-1">
            <span class="mb-1.5 block font-medium text-slate-700">Name</span>
            <input
              v-model="form.name"
              type="text"
              placeholder="Rule name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('name')"
            />
            <p v-if="fieldErrors.name" class="mt-1.5 text-xs text-rose-600">{{ fieldErrors.name[0] }}</p>
          </label>

          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Trigger type</span>
            <SelectBox
              v-model="form.trigger_type"
              wrapper-class="w-full"
              :options="triggerTypeOptions"
              :error="Boolean(fieldErrors.trigger_type)"
            />
            <p v-if="fieldErrors.trigger_type" class="mt-1.5 text-xs text-rose-600">
              {{ fieldErrors.trigger_type[0] }}
            </p>
          </label>

          <label v-if="form.trigger_type !== 'schedule'" class="block text-sm md:col-span-2">
            <span class="mb-1.5 block font-medium text-slate-700">Event</span>
            <SelectBox
              v-model="form.event_key"
              wrapper-class="w-full"
              placeholder="Select event"
              :options="eventOptions"
              :error="Boolean(fieldErrors.event_key)"
            />
            <p v-if="fieldErrors.event_key" class="mt-1.5 text-xs text-rose-600">
              {{ fieldErrors.event_key[0] }}
            </p>
          </label>

          <label v-if="form.trigger_type === 'schedule'" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Cron expression</span>
            <input
              v-model="form.schedule_cron"
              placeholder="0 8 * * *"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('schedule_cron')"
            />
            <p v-if="fieldErrors.schedule_cron" class="mt-1.5 text-xs text-rose-600">
              {{ fieldErrors.schedule_cron[0] }}
            </p>
          </label>

          <label v-if="form.trigger_type === 'schedule'" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Timezone</span>
            <input
              v-model="form.schedule_timezone"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>

          <label v-if="form.trigger_type === 'time'" class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Delay (minutes)</span>
            <input
              v-model.number="form.delay_minutes"
              type="number"
              min="1"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('delay_minutes')"
            />
            <p v-if="fieldErrors.delay_minutes" class="mt-1.5 text-xs text-rose-600">
              {{ fieldErrors.delay_minutes[0] }}
            </p>
          </label>

          <label class="block text-sm md:col-span-2">
            <span class="mb-1.5 block font-medium text-slate-700">Description</span>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Optional description"
              class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>

          <label
            class="inline-flex items-center gap-2.5 rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
          >
            <input
              v-model="form.is_enabled"
              type="checkbox"
              class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
            />
            Enabled
          </label>
        </div>
      </section>

      <!-- 2. Conditions -->
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">2. Conditions</h2>
            <p class="mt-0.5 text-sm text-slate-500">Optional filters evaluated before actions run.</p>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="form.condition_logic"
              wrapper-class="min-w-[10.5rem]"
              :options="logicOptions"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="addCondition()"
            >
              Add condition
            </button>
          </div>
        </div>

        <div
          v-if="!form.conditions.length"
          class="rounded-[12px] border border-dashed border-zinc-200 bg-zinc-50/50 px-4 py-10 text-center text-sm text-slate-500"
        >
          No conditions — rule always runs when triggered.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(condition, index) in form.conditions"
            :key="index"
            class="grid gap-3 rounded-[12px] bg-zinc-50 p-4 ring-1 ring-zinc-100 md:grid-cols-[1fr_1fr_1fr_auto]"
          >
            <input
              v-model="condition.field"
              placeholder="Field (e.g. priority)"
              class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <SelectBox
              v-model="condition.operator"
              wrapper-class="w-full"
              :options="operatorOptions"
            />
            <input
              v-model="condition.value"
              placeholder="Value"
              class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
            <button
              type="button"
              class="h-10 px-2 text-sm font-medium text-rose-600 hover:underline md:justify-self-end"
              @click="removeCondition(index)"
            >
              Remove
            </button>
          </div>
        </div>

        <div v-if="selectedEventFields.length" class="mt-4 flex flex-wrap items-center gap-2">
          <span class="text-xs font-medium text-slate-500">Suggested fields:</span>
          <button
            v-for="field in selectedEventFields"
            :key="field"
            type="button"
            class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-slate-600 ring-1 ring-zinc-200 hover:bg-zinc-50 hover:text-brand-700"
            @click="addCondition(field)"
          >
            {{ field }}
          </button>
        </div>
      </section>

      <!-- 3. Actions -->
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">3. Actions</h2>
            <p class="mt-0.5 text-sm text-slate-500">Executed in order when conditions pass.</p>
          </div>
          <button
            type="button"
            class="h-10 rounded-[12px] border border-zinc-200 px-4 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="addAction"
          >
            Add action
          </button>
        </div>

        <p v-if="fieldErrors.actions" class="mb-3 text-xs text-rose-600">{{ fieldErrors.actions[0] }}</p>

        <div
          v-if="!form.actions.length"
          class="rounded-[12px] border border-dashed border-zinc-200 bg-zinc-50/50 px-4 py-10 text-center text-sm text-slate-500"
        >
          No actions yet. Add at least one action to continue.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(action, index) in form.actions"
            :key="index"
            class="rounded-[12px] bg-zinc-50 p-4 ring-1 ring-zinc-100"
          >
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <SelectBox
                v-model="action.action_type"
                wrapper-class="min-w-[12rem] flex-1"
                :options="actionTypeOptions"
              />
              <label
                class="inline-flex h-10 items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-700"
              >
                <input
                  v-model="action.is_enabled"
                  type="checkbox"
                  class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
                />
                Enabled
              </label>
              <button
                type="button"
                class="ml-auto h-10 px-2 text-sm font-medium text-rose-600 hover:underline"
                @click="removeAction(index)"
              >
                Remove
              </button>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
              <input
                v-model="action.config.title"
                placeholder="Title / subject"
                class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
              <input
                v-model="action.config.message"
                placeholder="Message / body"
                class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
              <input
                v-if="action.action_type === 'assign_role'"
                v-model="action.config.role"
                placeholder="Role name (e.g. customer)"
                class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
              <input
                v-if="action.action_type === 'assign_agent'"
                v-model="action.config.assignee_uuid"
                placeholder="Assignee user UUID (optional)"
                class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 font-mono text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
              <input
                v-if="action.action_type === 'generate_api_key'"
                v-model="action.config.token_name"
                placeholder="Token name"
                class="h-10 rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              />
            </div>
          </div>
        </div>
      </section>

      <div class="flex flex-wrap items-center gap-3 rounded-[12px] bg-white px-6 py-4 ring-1 ring-zinc-100 sm:px-8">
        <button
          type="submit"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving…' : isEdit ? 'Update rule' : 'Create rule' }}
        </button>
        <button
          v-if="isEdit"
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="store.saving"
          @click="runTest"
        >
          Test run
        </button>
        <RouterLink
          :to="{ name: 'automation.rules' }"
          class="rounded-[12px] px-5 py-2.5 text-sm font-medium text-slate-500 hover:text-slate-800"
        >
          Cancel
        </RouterLink>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useToast } from '@/composables/useToast';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const fieldErrors = ref({});

const isEdit = computed(() => Boolean(route.params.id));

const form = reactive({
  name: '',
  description: '',
  trigger_type: 'event',
  event_key: '',
  schedule_cron: '',
  schedule_timezone: 'UTC',
  delay_minutes: null,
  condition_logic: 'and',
  is_enabled: true,
  priority: 100,
  conditions: [],
  actions: [
    {
      action_type: 'send_email',
      is_enabled: true,
      config: { title: '', message: '' },
    },
  ],
});

const logicOptions = [
  { value: 'and', label: 'Match ALL (AND)' },
  { value: 'or', label: 'Match ANY (OR)' },
];

const triggerTypeOptions = computed(() =>
  (store.catalog.trigger_types || []).map((item) => ({
    value: item.value,
    label: item.label,
  })),
);

const eventOptions = computed(() => [
  { value: '', label: 'Select event' },
  ...(store.catalog.events || []).map((item) => ({
    value: item.value,
    label: item.label,
  })),
]);

const operatorOptions = computed(() =>
  (store.catalog.operators || []).map((item) => ({
    value: item.value,
    label: item.label,
  })),
);

const actionTypeOptions = computed(() =>
  (store.catalog.actions || []).map((item) => ({
    value: item.value,
    label: `${item.label}${item.implemented ? '' : ' (future)'}`,
  })),
);

const selectedEventFields = computed(() => {
  const event = store.catalog.events.find((item) => item.value === form.event_key);
  return event?.sample_fields || [];
});

watch(
  () => store.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (message) {
      toast.success(message);
    }
  },
);

function fieldClass(field) {
  return fieldErrors.value?.[field]
    ? 'border-rose-400 focus:border-rose-500'
    : '';
}

function addCondition(field = '') {
  form.conditions.push({
    field: typeof field === 'string' ? field : '',
    operator: 'equals',
    value: '',
  });
}

function removeCondition(index) {
  form.conditions.splice(index, 1);
}

function addAction() {
  form.actions.push({
    action_type: 'send_notification',
    is_enabled: true,
    config: { title: '', message: '' },
  });
}

function removeAction(index) {
  form.actions.splice(index, 1);
}

function hydrate(rule) {
  form.name = rule.name || '';
  form.description = rule.description || '';
  form.trigger_type = rule.trigger_type || 'event';
  form.event_key = rule.event_key || '';
  form.schedule_cron = rule.schedule_cron || '';
  form.schedule_timezone = rule.schedule_timezone || 'UTC';
  form.delay_minutes = rule.delay_minutes;
  form.condition_logic = rule.condition_logic || 'and';
  form.is_enabled = Boolean(rule.is_enabled);
  form.priority = rule.priority || 100;
  form.conditions = (rule.conditions || []).map((item) => ({
    field: item.field,
    operator: item.operator,
    value: item.value ?? '',
  }));
  form.actions = (rule.actions || []).map((item) => ({
    action_type: item.action_type,
    is_enabled: item.is_enabled !== false,
    config: {
      title: item.config?.title || '',
      message: item.config?.message || item.config?.body || '',
      role: item.config?.role || '',
      assignee_uuid: item.config?.assignee_uuid || '',
      token_name: item.config?.token_name || '',
    },
  }));
  if (!form.actions.length) {
    addAction();
  }
  fieldErrors.value = {};
}

function validate() {
  const next = {};

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.trigger_type || '').trim()) {
    next.trigger_type = ['The trigger type field is required.'];
  }

  if (form.trigger_type !== 'schedule' && !String(form.event_key || '').trim()) {
    next.event_key = ['Please select an event.'];
  }

  if (form.trigger_type === 'schedule' && !String(form.schedule_cron || '').trim()) {
    next.schedule_cron = ['The cron expression field is required.'];
  }

  if (form.trigger_type === 'time') {
    const delay = Number(form.delay_minutes);
    if (!Number.isInteger(delay) || delay < 1) {
      next.delay_minutes = ['Delay must be at least 1 minute.'];
    }
  }

  if (!form.actions.length) {
    next.actions = ['Add at least one action.'];
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
    trigger_type: form.trigger_type,
    event_key: form.event_key || null,
    schedule_cron: form.schedule_cron || null,
    schedule_timezone: form.schedule_timezone || 'UTC',
    delay_minutes: form.trigger_type === 'time' ? form.delay_minutes : null,
    condition_logic: form.condition_logic,
    is_enabled: form.is_enabled,
    priority: form.priority,
    conditions: form.conditions.filter((item) => item.field && item.operator),
    actions: form.actions.map((item, index) => ({
      action_type: item.action_type,
      is_enabled: item.is_enabled,
      sort_order: index,
      config: Object.fromEntries(
        Object.entries(item.config || {}).filter(([, value]) => value !== '' && value != null),
      ),
    })),
  };

  try {
    const saved = await store.saveRule(payload, isEdit.value ? route.params.id : null);
    if (!isEdit.value && saved?.uuid) {
      await router.push({ name: 'automation.rules.edit', params: { id: saved.uuid } });
    }
  } catch {
    // Store sets error; toast watch handles display.
  }
}

async function runTest() {
  try {
    const result = await store.testRule(route.params.id, {
      priority: 'high',
      subject: 'Automation test context',
    });
    toast.success(`Test status: ${result?.status || 'unknown'}. ${result?.message || ''}`.trim());
  } catch {
    // Store sets error; toast watch handles display.
  }
}

onMounted(async () => {
  await store.fetchCatalog();
  if (isEdit.value) {
    const rule = await store.fetchRule(route.params.id);
    hydrate(rule);
  }
});
</script>

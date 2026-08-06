<template>
  <div>
    <PageHeader
      :title="isEdit ? 'Edit Automation Rule' : 'Create Automation Rule'"
      description="Visual rule builder for triggers, conditions, and actions."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'automation.rules' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to rules
        </RouterLink>
      </template>
    </PageHeader>

    <AutomationSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>
    <div v-if="store.successMessage" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
      {{ store.successMessage }}
    </div>

    <form class="space-y-6" @submit.prevent="submit">
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">1. Trigger</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Name</span>
            <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Trigger type</span>
            <select v-model="form.trigger_type" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option v-for="item in store.catalog.trigger_types" :key="item.value" :value="item.value">
                {{ item.label }}
              </option>
            </select>
          </label>
          <label v-if="form.trigger_type !== 'schedule'" class="block text-sm md:col-span-2">
            <span class="mb-1 block font-medium text-slate-700">Event</span>
            <select v-model="form.event_key" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option value="">Select event</option>
              <option v-for="item in store.catalog.events" :key="item.value" :value="item.value">
                {{ item.label }}
              </option>
            </select>
          </label>
          <label v-if="form.trigger_type === 'schedule'" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Cron expression</span>
            <input v-model="form.schedule_cron" placeholder="0 8 * * *" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label v-if="form.trigger_type === 'schedule'" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Timezone</span>
            <input v-model="form.schedule_timezone" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label v-if="form.trigger_type === 'time'" class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Delay (minutes)</span>
            <input v-model.number="form.delay_minutes" type="number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block font-medium text-slate-700">Description</span>
            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
            Enabled
          </label>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">2. Conditions</h2>
            <p class="text-xs text-slate-500">Optional filters evaluated before actions run.</p>
          </div>
          <div class="flex items-center gap-2">
            <select v-model="form.condition_logic" class="rounded-lg border border-slate-300 px-2 py-1.5 text-sm">
              <option value="and">Match ALL (AND)</option>
              <option value="or">Match ANY (OR)</option>
            </select>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-50" @click="addCondition">
              Add condition
            </button>
          </div>
        </div>

        <div v-if="!form.conditions.length" class="rounded-lg border border-dashed border-slate-200 px-4 py-6 text-center text-sm text-slate-500">
          No conditions — rule always runs when triggered.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="(condition, index) in form.conditions"
            :key="index"
            class="grid gap-2 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[1fr_1fr_1fr_auto]"
          >
            <input v-model="condition.field" placeholder="Field (e.g. priority)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            <select v-model="condition.operator" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
              <option v-for="op in store.catalog.operators" :key="op.value" :value="op.value">{{ op.label }}</option>
            </select>
            <input v-model="condition.value" placeholder="Value" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
            <button type="button" class="text-sm text-rose-600 hover:underline" @click="removeCondition(index)">Remove</button>
          </div>
        </div>

        <div v-if="selectedEventFields.length" class="mt-3 text-xs text-slate-500">
          Suggested fields:
          <button
            v-for="field in selectedEventFields"
            :key="field"
            type="button"
            class="ml-1 rounded bg-white px-1.5 py-0.5 ring-1 ring-slate-200 hover:bg-slate-50"
            @click="addCondition(field)"
          >
            {{ field }}
          </button>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">3. Actions</h2>
            <p class="text-xs text-slate-500">Executed in order when conditions pass.</p>
          </div>
          <button type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-50" @click="addAction">
            Add action
          </button>
        </div>

        <div class="space-y-3">
          <div
            v-for="(action, index) in form.actions"
            :key="index"
            class="rounded-lg border border-slate-100 bg-slate-50 p-3"
          >
            <div class="mb-2 flex flex-wrap items-center gap-2">
              <select v-model="action.action_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option v-for="item in store.catalog.actions" :key="item.value" :value="item.value">
                  {{ item.label }}{{ item.implemented ? '' : ' (future)' }}
                </option>
              </select>
              <label class="flex items-center gap-1 text-xs text-slate-600">
                <input v-model="action.is_enabled" type="checkbox" class="rounded border-slate-300" />
                Enabled
              </label>
              <button type="button" class="ml-auto text-sm text-rose-600 hover:underline" @click="removeAction(index)">
                Remove
              </button>
            </div>
            <div class="grid gap-2 md:grid-cols-2">
              <input v-model="action.config.title" placeholder="Title / subject" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              <input v-model="action.config.message" placeholder="Message / body" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
              <input
                v-if="action.action_type === 'assign_role'"
                v-model="action.config.role"
                placeholder="Role name (e.g. customer)"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
              />
              <input
                v-if="action.action_type === 'assign_agent'"
                v-model="action.config.assignee_uuid"
                placeholder="Assignee user UUID (optional)"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
              />
              <input
                v-if="action.action_type === 'generate_api_key'"
                v-model="action.config.token_name"
                placeholder="Token name"
                class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
              />
            </div>
          </div>
        </div>
      </section>

      <div class="flex flex-wrap gap-3">
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving...' : isEdit ? 'Update rule' : 'Create rule' }}
        </button>
        <button
          v-if="isEdit"
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="runTest"
        >
          Test run
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();
const route = useRoute();
const router = useRouter();

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

const selectedEventFields = computed(() => {
  const event = store.catalog.events.find((item) => item.value === form.event_key);
  return event?.sample_fields || [];
});

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
}

async function submit() {
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

  const saved = await store.saveRule(payload, isEdit.value ? route.params.id : null);
  if (!isEdit.value && saved?.uuid) {
    await router.push({ name: 'automation.rules.edit', params: { id: saved.uuid } });
  }
}

async function runTest() {
  const result = await store.testRule(route.params.id, {
    priority: 'high',
    subject: 'Automation test context',
  });
  window.alert(`Test status: ${result?.status || 'unknown'}\n${result?.message || ''}`);
}

onMounted(async () => {
  await store.fetchCatalog();
  if (isEdit.value) {
    const rule = await store.fetchRule(route.params.id);
    hydrate(rule);
  }
});
</script>

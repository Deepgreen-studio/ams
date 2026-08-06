<template>
  <div>
    <PageHeader
      title="Automation Rules"
      description="Manage event, scheduled, delayed, and conditional automation rules."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'automation.rules.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create rule
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

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search rules..."
        class="w-full max-w-xs rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <select v-model="filters.trigger_type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All triggers</option>
        <option v-for="item in store.catalog.trigger_types" :key="item.value" :value="item.value">
          {{ item.label }}
        </option>
      </select>
      <select v-model="filters.is_enabled" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All statuses</option>
        <option value="1">Enabled</option>
        <option value="0">Disabled</option>
      </select>
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="load"
      >
        Apply
      </button>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Rule</th>
            <th class="px-4 py-3">Trigger</th>
            <th class="px-4 py-3">Event / Schedule</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="store.loading">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">Loading rules...</td>
          </tr>
          <tr v-else-if="!store.rules.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No automation rules found.</td>
          </tr>
          <tr v-for="rule in store.rules" :key="rule.uuid" class="hover:bg-slate-50/70">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ rule.name }}</p>
              <p class="text-xs text-slate-500">{{ rule.description || 'No description' }}</p>
            </td>
            <td class="px-4 py-3 text-slate-700">{{ rule.trigger_type_label || rule.trigger_type }}</td>
            <td class="px-4 py-3 text-slate-600">
              <span v-if="rule.trigger_type === 'schedule'">{{ rule.schedule_cron }}</span>
              <span v-else>{{ rule.event_key }}</span>
              <span v-if="rule.delay_minutes" class="ml-1 text-xs text-slate-400">(+{{ rule.delay_minutes }}m)</span>
            </td>
            <td class="px-4 py-3">
              <button
                type="button"
                class="rounded-full px-2.5 py-1 text-xs font-medium"
                :class="rule.is_enabled ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                @click="toggle(rule)"
              >
                {{ rule.is_enabled ? 'Enabled' : 'Disabled' }}
              </button>
            </td>
            <td class="px-4 py-3 text-right">
              <RouterLink
                :to="{ name: 'automation.rules.edit', params: { id: rule.uuid } }"
                class="mr-3 text-sm font-medium text-brand-700 hover:underline"
              >
                Edit
              </RouterLink>
              <button type="button" class="text-sm font-medium text-rose-600 hover:underline" @click="remove(rule)">
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
import PageHeader from '@/components/ui/PageHeader.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();
const filters = reactive({
  search: '',
  trigger_type: '',
  is_enabled: '',
});

async function load() {
  await store.fetchRules({ ...filters });
}

async function toggle(rule) {
  await store.toggleRule(rule.uuid, !rule.is_enabled);
}

async function remove(rule) {
  if (!window.confirm(`Delete rule "${rule.name}"?`)) {
    return;
  }
  await store.deleteRule(rule.uuid);
}

onMounted(load);
</script>

<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'automation.rules.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        Create rule
      </RouterLink>
    </Teleport>

    <AutomationSubnav />

    <div class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <div class="border-b border-zinc-100 px-6 py-5 sm:px-8 sm:py-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div class="relative min-w-0 flex-1 lg:max-w-sm">
            <MagnifyingGlassIcon
              class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            />
            <input
              v-model="filters.search"
              type="search"
              placeholder="Search rules…"
              class="h-10 w-full rounded-[12px] border border-zinc-200 bg-white py-2 pl-10 pr-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <SelectBox
              v-model="filters.trigger_type"
              wrapper-class="min-w-[9.5rem]"
              :options="triggerOptions"
              @change="applyFilters"
            />
            <SelectBox
              v-model="filters.is_enabled"
              wrapper-class="min-w-[8.5rem]"
              :options="statusOptions"
              @change="applyFilters"
            />
            <button
              type="button"
              class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
              @click="applyFilters"
            >
              Apply
            </button>
            <button
              type="button"
              class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="resetFilters"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <div v-if="store.loading" class="space-y-3 px-6 py-6 sm:px-8">
        <div v-for="n in 5" :key="n" class="h-14 animate-pulse rounded-[12px] bg-zinc-100" />
      </div>

      <EmptyState
        v-else-if="!store.rules.length"
        title="No automation rules found"
        description="Try adjusting your filters or create a new automation rule."
        class="px-6 py-10 sm:px-8"
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
            @click="resetFilters"
          >
            Reset
          </button>
          <RouterLink
            :to="{ name: 'automation.rules.create' }"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          >
            Create rule
          </RouterLink>
        </template>
      </EmptyState>

      <div v-else class="overflow-x-auto px-3">
        <table class="min-w-full text-sm">
          <thead>
            <tr class="border-b border-zinc-100">
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Rule</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Trigger</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Event / Schedule</th>
              <th class="px-5 py-3 text-left text-sm font-semibold text-zinc-500">Status</th>
              <th class="px-5 py-3 text-right text-sm font-semibold text-zinc-500">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="rule in store.rules"
              :key="rule.uuid"
              class="border-b border-zinc-50 last:border-0 transition hover:bg-zinc-50/80"
            >
              <td class="max-w-md px-5 py-4">
                <p class="font-medium text-slate-900">{{ rule.name }}</p>
                <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">
                  {{ rule.description || 'No description' }}
                </p>
              </td>
              <td class="px-5 py-4 text-slate-700">
                {{ rule.trigger_type_label || rule.trigger_type }}
              </td>
              <td class="px-5 py-4">
                <span class="font-mono text-xs text-slate-600">
                  <template v-if="rule.trigger_type === 'schedule'">{{ rule.schedule_cron }}</template>
                  <template v-else>{{ rule.event_key }}</template>
                </span>
                <span v-if="rule.delay_minutes" class="ml-1 text-xs text-slate-400">
                  (+{{ rule.delay_minutes }}m)
                </span>
              </td>
              <td class="px-5 py-4">
                <Tooltip :text="rule.is_enabled ? 'Disable rule' : 'Enable rule'">
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="rule.is_enabled"
                    :aria-label="rule.is_enabled ? 'Disable rule' : 'Enable rule'"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                    :class="rule.is_enabled ? 'bg-brand-600' : 'bg-zinc-300'"
                    @click="toggle(rule)"
                  >
                    <span
                      class="pointer-events-none inline-block h-4 w-4 rounded-full bg-white shadow transition-transform"
                      :class="rule.is_enabled ? 'translate-x-6' : 'translate-x-1'"
                    />
                  </button>
                </Tooltip>
              </td>
              <td class="whitespace-nowrap px-5 py-4">
                <div class="flex items-center justify-end gap-1">
                  <Tooltip text="Edit">
                    <RouterLink
                      :to="{ name: 'automation.rules.edit', params: { id: rule.uuid } }"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-zinc-100 hover:text-slate-800"
                      aria-label="Edit rule"
                    >
                      <PencilSquareIcon class="h-4 w-4" />
                    </RouterLink>
                  </Tooltip>
                  <Tooltip text="Delete">
                    <button
                      type="button"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-[12px] text-slate-500 transition hover:bg-rose-50 hover:text-rose-600"
                      aria-label="Delete rule"
                      @click="remove(rule)"
                    >
                      <TrashIcon class="h-4 w-4" />
                    </button>
                  </Tooltip>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="store.ruleMeta?.total"
        class="border-t border-zinc-100 px-6 py-4 sm:px-8"
      >
        <Pagination
          :meta="store.ruleMeta"
          :loading="store.loading"
          @change="onPageChange"
          @per-page="onPerPageChange"
        />
      </div>
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete automation rule"
      :message="`Delete “${pendingDelete?.name || 'this rule'}”? This cannot be undone.`"
      confirm-label="Delete"
      :loading="deleting"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import {
  MagnifyingGlassIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import EmptyState from '@/components/ui/EmptyState.vue';
import Tooltip from '@/components/ui/Tooltip.vue';
import { useToast } from '@/composables/useToast';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import AutomationSubnav from '@/modules/automation/components/AutomationSubnav.vue';
import { useAutomationStore } from '@/modules/automation/stores/automation';

const store = useAutomationStore();
const toast = useToast();
const pendingDelete = ref(null);
const deleting = ref(false);

const filters = reactive({
  search: '',
  trigger_type: '',
  is_enabled: '',
  page: 1,
  per_page: 20,
});

const statusOptions = [
  { value: '', label: 'All statuses' },
  { value: '1', label: 'Enabled' },
  { value: '0', label: 'Disabled' },
];

const triggerOptions = computed(() => [
  { value: '', label: 'All triggers' },
  ...(store.catalog.trigger_types || []).map((item) => ({
    value: item.value,
    label: item.label,
  })),
]);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});

async function reload() {
  await store.fetchRules({
    search: filters.search || undefined,
    trigger_type: filters.trigger_type || undefined,
    is_enabled: filters.is_enabled === '' ? undefined : filters.is_enabled,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function applyFilters() {
  filters.page = 1;
  reload();
}

function resetFilters() {
  filters.search = '';
  filters.trigger_type = '';
  filters.is_enabled = '';
  filters.page = 1;
  reload();
}

function onPageChange(page) {
  filters.page = page;
  reload();
}

function onPerPageChange(perPage) {
  filters.per_page = perPage;
  filters.page = 1;
  reload();
}

async function toggle(rule) {
  await store.toggleRule(rule.uuid, !rule.is_enabled);
}

function remove(rule) {
  pendingDelete.value = rule;
}

async function confirmDelete() {
  if (!pendingDelete.value) return;

  deleting.value = true;
  try {
    await store.deleteRule(pendingDelete.value.uuid);
    pendingDelete.value = null;
    if (!store.rules.length && filters.page > 1) {
      filters.page -= 1;
      await reload();
    }
  } catch {
    // Toast watch handles store.error
  } finally {
    deleting.value = false;
  }
}
</script>

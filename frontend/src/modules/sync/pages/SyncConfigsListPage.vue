<template>
  <div>
    <PageHeader
      title="Sync Configs"
      description="Configure import/export synchronization for connected APIs."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'sync.configs.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          Create config
        </RouterLink>
      </template>
    </PageHeader>
    <SyncSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <form
      class="mb-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 md:flex-row md:items-end"
      @submit.prevent="store.fetchConfigs({ ...filters, page: 1 })"
    >
      <div class="flex-1">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Search</label
        >
        <input
          v-model="filters.search"
          type="search"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
          placeholder="Name, slug, entity..."
        />
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Direction</label
        >
        <select
          v-model="filters.direction"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="import">Import</option>
          <option value="export">Export</option>
          <option value="bidirectional">Bidirectional</option>
        </select>
      </div>
      <div class="w-full md:w-40">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Trigger</label
        >
        <select
          v-model="filters.trigger_type"
          class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
        >
          <option value="">All</option>
          <option value="manual">Manual</option>
          <option value="automatic">Automatic</option>
          <option value="scheduled">Scheduled</option>
        </select>
      </div>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
      >
        Filter
      </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <div v-if="store.loading" class="space-y-3 p-6">
        <div v-for="n in 5" :key="n" class="h-10 animate-pulse rounded bg-slate-100" />
      </div>
      <div v-else-if="!store.configs.length" class="px-6 py-12 text-center text-sm text-slate-500">
        No sync configs found.
      </div>
      <table v-else class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Config</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Direction</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Trigger</th>
            <th class="px-4 py-3 text-left font-semibold text-slate-600">Status</th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="item in store.configs" :key="item.uuid" class="hover:bg-slate-50/80">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">
                {{ item.integration?.name || '—' }} · {{ item.company?.company_name || '—' }}
              </p>
            </td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.direction }}</td>
            <td class="px-4 py-3 capitalize text-slate-700">{{ item.trigger_type }}</td>
            <td
              class="px-4 py-3 capitalize"
              :class="item.is_enabled ? 'text-emerald-700' : 'text-slate-500'"
            >
              {{ item.is_enabled ? 'enabled' : 'disabled' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <RouterLink
                  :to="{ name: 'sync.configs.show', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
                  >View</RouterLink
                >
                <RouterLink
                  :to="{ name: 'sync.configs.edit', params: { id: item.uuid } }"
                  class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100"
                  >Edit</RouterLink
                >
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <Pagination
      :meta="store.configsMeta"
      :loading="store.loading"
      @change="(page) => store.fetchConfigs({ page })"
    />
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import Pagination from '@/modules/users/components/Pagination.vue';
import SyncSubnav from '@/modules/sync/components/SyncSubnav.vue';
import { useSyncStore } from '@/modules/sync/stores/sync';

const store = useSyncStore();
const filters = reactive({ search: '', direction: '', trigger_type: '' });

onMounted(() => store.fetchConfigs());
</script>

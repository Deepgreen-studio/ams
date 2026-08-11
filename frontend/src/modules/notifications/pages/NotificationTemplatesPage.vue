<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates.approvals' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Approvals
      </RouterLink>
      <RouterLink
        :to="{ name: 'notifications.templates.create' }"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
      >
        New template
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div class="mb-4 flex flex-wrap gap-2 rounded-[12px] bg-white p-4 ring-1 ring-zinc-100">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search templates…"
        class="min-w-[12rem] flex-1 rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @keyup.enter="reload"
      />
      <select
        v-model="filters.channel"
        class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @change="reload"
      >
        <option value="">All channels</option>
        <option v-for="channel in store.templateChannels" :key="channel.value" :value="channel.value">
          {{ channel.label }}
        </option>
      </select>
      <select
        v-model="filters.locale"
        class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @change="reload"
      >
        <option value="">All locales</option>
        <option v-for="locale in store.templateLocales" :key="locale.value" :value="locale.value">
          {{ locale.label }}
        </option>
      </select>
      <select
        v-model="filters.workflow_status"
        class="rounded-[12px] border border-zinc-200 px-3 py-2 text-sm text-slate-700"
        @change="reload"
      >
        <option value="">All statuses</option>
        <option v-for="status in store.templateWorkflowStatuses" :key="status.value" :value="status.value">
          {{ status.label }}
        </option>
      </select>
      <button
        type="button"
        class="rounded-[12px] border border-zinc-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="reload"
      >
        Filter
      </button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-[12px] bg-zinc-100" />

    <div v-else class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100">
      <table class="min-w-full divide-y divide-zinc-100 text-sm">
        <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-5 py-3.5">Template</th>
            <th class="px-5 py-3.5">Channel</th>
            <th class="px-5 py-3.5">Locale</th>
            <th class="px-5 py-3.5">Workflow</th>
            <th class="px-5 py-3.5">Version</th>
            <th class="px-5 py-3.5 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100">
          <tr v-if="!store.templates.length">
            <td colspan="6" class="px-5 py-12 text-center text-slate-500">No templates found.</td>
          </tr>
          <tr v-for="item in store.templates" :key="item.uuid" class="hover:bg-zinc-50/80">
            <td class="px-5 py-4">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.event_label }} · {{ item.subject || 'No subject' }}</p>
            </td>
            <td class="px-5 py-4 text-slate-600">{{ item.channel_label }}</td>
            <td class="px-5 py-4 uppercase text-slate-600">{{ item.locale }}</td>
            <td class="px-5 py-4">
              <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                {{ item.workflow_status_label }}
              </span>
            </td>
            <td class="px-5 py-4 text-slate-600">v{{ item.current_version }}</td>
            <td class="space-x-3 px-5 py-4 text-right">
              <RouterLink
                :to="{ name: 'notifications.templates.edit', params: { id: item.uuid } }"
                class="font-medium text-brand-700 hover:underline"
              >
                Edit
              </RouterLink>
              <RouterLink
                :to="{ name: 'notifications.templates.preview', params: { id: item.uuid } }"
                class="text-slate-600 hover:underline"
              >
                Preview
              </RouterLink>
              <RouterLink
                :to="{ name: 'notifications.templates.versions', params: { id: item.uuid } }"
                class="text-slate-600 hover:underline"
              >
                Versions
              </RouterLink>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4">
      <Pagination :meta="store.templateMeta" @change="onPageChange" />
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';
import Pagination from '@/modules/users/components/Pagination.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const filters = reactive({
  search: '',
  channel: '',
  locale: '',
  workflow_status: '',
  page: 1,
  per_page: 20,
});

onMounted(reload);

async function reload() {
  await store.fetchTemplates({
    search: filters.search || undefined,
    channel: filters.channel || undefined,
    locale: filters.locale || undefined,
    workflow_status: filters.workflow_status || undefined,
    page: filters.page,
    per_page: filters.per_page,
  });
}

function onPageChange(page) {
  filters.page = page;
  reload();
}
</script>

<template>
  <div>
    <PageHeader title="Template Manager" description="Enterprise notification templates across email, push, SMS, and more.">
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.templates.approvals' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Approvals
        </RouterLink>
        <RouterLink
          :to="{ name: 'notifications.templates.create' }"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >
          New template
        </RouterLink>
      </template>
    </PageHeader>

    <NotificationsSubnav />

    <div class="mb-4 flex flex-wrap gap-2">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search templates…"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="reload"
      />
      <select v-model="filters.channel" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All channels</option>
        <option v-for="channel in store.templateChannels" :key="channel.value" :value="channel.value">
          {{ channel.label }}
        </option>
      </select>
      <select v-model="filters.locale" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All locales</option>
        <option v-for="locale in store.templateLocales" :key="locale.value" :value="locale.value">
          {{ locale.label }}
        </option>
      </select>
      <select v-model="filters.workflow_status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="reload">
        <option value="">All statuses</option>
        <option v-for="status in store.templateWorkflowStatuses" :key="status.value" :value="status.value">
          {{ status.label }}
        </option>
      </select>
      <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @click="reload">Filter</button>
    </div>

    <div v-if="store.loading" class="h-40 animate-pulse rounded-xl bg-slate-100" />

    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
          <tr>
            <th class="px-4 py-3">Template</th>
            <th class="px-4 py-3">Channel</th>
            <th class="px-4 py-3">Locale</th>
            <th class="px-4 py-3">Workflow</th>
            <th class="px-4 py-3">Version</th>
            <th class="px-4 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-if="!store.templates.length">
            <td colspan="6" class="px-4 py-10 text-center text-slate-500">No templates found.</td>
          </tr>
          <tr v-for="item in store.templates" :key="item.uuid" class="hover:bg-slate-50">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ item.name }}</p>
              <p class="text-xs text-slate-500">{{ item.event_label }} · {{ item.subject || 'No subject' }}</p>
            </td>
            <td class="px-4 py-3">{{ item.channel_label }}</td>
            <td class="px-4 py-3 uppercase">{{ item.locale }}</td>
            <td class="px-4 py-3">
              <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                {{ item.workflow_status_label }}
              </span>
            </td>
            <td class="px-4 py-3">v{{ item.current_version }}</td>
            <td class="px-4 py-3 text-right space-x-2">
              <RouterLink :to="{ name: 'notifications.templates.edit', params: { id: item.uuid } }" class="text-brand-700 hover:underline">
                Edit
              </RouterLink>
              <RouterLink :to="{ name: 'notifications.templates.preview', params: { id: item.uuid } }" class="text-slate-600 hover:underline">
                Preview
              </RouterLink>
              <RouterLink :to="{ name: 'notifications.templates.versions', params: { id: item.uuid } }" class="text-slate-600 hover:underline">
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
import PageHeader from '@/components/ui/PageHeader.vue';
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

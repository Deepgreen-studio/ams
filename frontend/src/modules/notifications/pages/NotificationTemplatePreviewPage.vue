<template>
  <div>
    <PageHeader title="Template Preview & Test Send" description="Render placeholders and send a test notification.">
      <template #actions>
        <RouterLink
          :to="{ name: 'notifications.templates.edit', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Edit
        </RouterLink>
      </template>
    </PageHeader>

    <NotificationsSubnav />

    <p v-if="store.successMessage" class="mb-4 text-sm text-emerald-700">{{ store.successMessage }}</p>
    <p v-if="error" class="mb-4 text-sm text-rose-600">{{ error }}</p>

    <div class="grid gap-4 lg:grid-cols-2">
      <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
        <h2 class="text-sm font-semibold text-slate-900">{{ template?.name || 'Template' }}</h2>
        <p class="text-xs text-slate-500">
          {{ template?.channel_label }} · {{ template?.locale }} · {{ template?.workflow_status_label }}
        </p>
        <div class="space-y-2">
          <label v-for="variable in variableKeys" :key="variable" class="block text-xs">
            <span class="mb-1 block font-medium text-slate-600">{{ variable }}</span>
            <input v-model="variables[variable]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
          </label>
        </div>
        <div class="flex flex-wrap gap-2">
          <button type="button" class="rounded-lg bg-brand-600 px-4 py-2 text-sm text-white" @click="runPreview">
            Refresh preview
          </button>
          <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" :disabled="store.saving" @click="runTest">
            {{ store.saving ? 'Sending…' : 'Send test' }}
          </button>
        </div>
        <input
          v-model="testEmail"
          type="email"
          placeholder="Test email (optional for email channel)"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
        />
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-3 text-sm font-semibold text-slate-900">Preview</h2>
        <p class="text-xs uppercase tracking-wide text-slate-500">Subject</p>
        <p class="mt-1 text-sm font-medium text-slate-900">{{ preview?.subject || '—' }}</p>
        <p class="mt-4 text-xs uppercase tracking-wide text-slate-500">Body</p>
        <div
          v-if="isHtml"
          class="prose mt-2 max-w-none rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm"
          v-html="preview?.body || ''"
        />
        <pre v-else class="mt-2 whitespace-pre-wrap rounded-lg border border-slate-100 bg-slate-50 p-4 text-sm text-slate-700">{{ preview?.body || '—' }}</pre>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const route = useRoute();
const template = ref(null);
const preview = ref(null);
const error = ref('');
const testEmail = ref('');
const variables = reactive({});

const variableKeys = computed(() => Object.keys(variables));
const isHtml = computed(() => ['email', 'webhook', 'slack', 'teams'].includes(template.value?.channel));

onMounted(async () => {
  template.value = await store.fetchTemplate(route.params.id);
  const keys = template.value.available_variables?.length
    ? template.value.available_variables
    : store.templateEvents.find((item) => item.value === template.value.event_key)?.variables || [];
  keys.forEach((key) => {
    variables[key] = '';
  });
  await runPreview();
});

async function runPreview() {
  error.value = '';
  try {
    preview.value = await store.previewTemplate(route.params.id, { ...variables });
  } catch (err) {
    error.value = err?.response?.data?.message || store.error || 'Preview failed';
  }
}

async function runTest() {
  error.value = '';
  store.successMessage = null;
  try {
    await store.testSendTemplate(route.params.id, {
      email: testEmail.value || undefined,
      variables: { ...variables },
    });
  } catch (err) {
    error.value = err?.response?.data?.message || store.error || 'Test send failed';
  }
}
</script>

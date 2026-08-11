<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates.edit', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Edit
      </RouterLink>
    </Teleport>

    <NotificationsSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <section class="space-y-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <div>
          <h2 class="text-base font-semibold text-slate-900">{{ template?.name || 'Template' }}</h2>
          <p class="mt-1 text-xs text-slate-500">
            {{ template?.channel_label }} · {{ template?.locale }} · {{ template?.workflow_status_label }}
          </p>
        </div>
        <div class="space-y-3">
          <label v-for="variable in variableKeys" :key="variable" class="block text-xs">
            <span class="mb-1.5 block font-medium text-slate-600">{{ variable }}</span>
            <input
              v-model="variables[variable]"
              class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900"
            />
          </label>
        </div>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="runPreview"
          >
            Refresh preview
          </button>
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
            :disabled="store.saving"
            @click="runTest"
          >
            {{ store.saving ? 'Sending…' : 'Send test' }}
          </button>
        </div>
        <input
          v-model="testEmail"
          type="email"
          placeholder="Test email (optional for email channel)"
          class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900"
        />
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
        <h2 class="mb-4 text-base font-semibold text-slate-900">Preview</h2>
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Subject</p>
        <p class="mt-1 text-sm font-medium text-slate-900">{{ preview?.subject || '—' }}</p>
        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-slate-500">Body</p>
        <div
          v-if="isHtml"
          class="prose mt-2 max-w-none rounded-[12px] bg-zinc-50 p-4 text-sm ring-1 ring-zinc-100"
          v-html="preview?.body || ''"
        />
        <pre
          v-else
          class="mt-2 whitespace-pre-wrap rounded-[12px] bg-zinc-50 p-4 text-sm text-slate-700 ring-1 ring-zinc-100"
        >{{ preview?.body || '—' }}</pre>
      </section>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
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

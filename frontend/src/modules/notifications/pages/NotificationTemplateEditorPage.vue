<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'notifications.templates' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
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
      v-if="formError"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ formError }}
    </div>

    <form class="grid gap-4 lg:grid-cols-3" @submit.prevent="save">
      <div class="space-y-4 lg:col-span-2">
        <div class="space-y-4 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <input
            v-model="form.name"
            required
            placeholder="Template name"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900"
          />
          <div class="grid gap-3 sm:grid-cols-2">
            <select
              v-model="form.event_key"
              required
              class="rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
              :disabled="isEdit && form.is_system"
            >
              <option v-for="event in store.templateEvents" :key="event.value" :value="event.value">
                {{ event.label }}
              </option>
            </select>
            <select
              v-model="form.channel"
              required
              class="rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
            >
              <option v-for="channel in store.templateChannels" :key="channel.value" :value="channel.value">
                {{ channel.label }}{{ channel.implemented ? '' : ' (Future)' }}
              </option>
            </select>
          </div>
          <div class="grid gap-3 sm:grid-cols-3">
            <select
              v-model="form.locale"
              class="rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
            >
              <option v-for="locale in store.templateLocales" :key="locale.value" :value="locale.value">
                {{ locale.label }}
              </option>
            </select>
            <select
              v-model="form.priority"
              class="rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
            >
              <option value="low">Low</option>
              <option value="normal">Normal</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
            <label
              class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
            >
              <input v-model="form.is_active" type="checkbox" class="rounded border-zinc-300 text-brand-600" />
              Active
            </label>
          </div>
          <input
            v-if="usesSubject"
            v-model="form.subject"
            placeholder="Subject / title"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900"
          />
          <div v-if="usesRichEditor">
            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">HTML body</p>
            <TemplateRichTextEditor v-model="form.body" :variables="availableVariables" />
          </div>
          <textarea
            v-else
            v-model="form.body"
            required
            rows="10"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 font-mono text-sm text-slate-900"
            placeholder="Plain text body with {{placeholders}}"
          />
          <input
            v-model="form.change_summary"
            placeholder="Change summary"
            class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900"
          />
        </div>
      </div>

      <div class="space-y-4">
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Channel configuration</h3>
          <p class="mt-1 text-xs text-slate-500">{{ channelHelp }}</p>
          <ul class="mt-3 space-y-2 text-xs text-slate-600">
            <li>Email / Webhook / Slack / Teams: HTML or rich text supported</li>
            <li>Push / SMS / WhatsApp / In-App: plain text preferred</li>
            <li>Use dynamic placeholders like <code v-pre>{{ticket_number}}</code></li>
          </ul>
        </div>

        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <h3 class="text-base font-semibold text-slate-900">Variables</h3>
          <div class="mt-3 flex flex-wrap gap-2">
            <button
              v-for="variable in availableVariables"
              :key="variable"
              type="button"
              class="rounded-[8px] bg-zinc-100 px-2 py-1 font-mono text-[11px] text-slate-700 hover:bg-brand-50 hover:text-brand-700"
              @click="appendVariable(variable)"
            >
              {{ '{' + '{' + variable + '}' + '}' }}
            </button>
          </div>
        </div>

        <div class="space-y-2 rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
          <button
            type="submit"
            class="w-full rounded-[12px] bg-brand-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="store.saving"
          >
            {{ store.saving ? 'Saving…' : 'Save template' }}
          </button>
          <template v-if="isEdit">
            <button
              type="button"
              class="w-full rounded-[12px] border border-zinc-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="goPreview"
            >
              Preview
            </button>
            <button
              type="button"
              class="w-full rounded-[12px] border border-zinc-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="submitReview"
            >
              Submit for review
            </button>
            <button
              type="button"
              class="w-full rounded-[12px] border border-zinc-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="publish"
            >
              Publish
            </button>
            <button
              type="button"
              class="w-full rounded-[12px] border border-zinc-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
              @click="goVersions"
            >
              Version history
            </button>
          </template>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import NotificationsSubnav from '@/modules/notifications/components/NotificationsSubnav.vue';
import TemplateRichTextEditor from '@/modules/notifications/components/TemplateRichTextEditor.vue';
import { useNotificationsStore } from '@/modules/notifications/stores/notifications';

const store = useNotificationsStore();
const route = useRoute();
const router = useRouter();
const formError = ref('');

const form = reactive({
  name: '',
  event_key: 'support.ticket_created',
  channel: 'email',
  locale: 'en',
  subject: '',
  body: '',
  priority: 'normal',
  is_active: true,
  is_system: false,
  change_summary: '',
  available_variables: [],
});

const isEdit = computed(() => !!route.params.id);
const usesRichEditor = computed(() => ['email', 'webhook', 'slack', 'teams'].includes(form.channel));
const usesSubject = computed(() => ['email', 'push', 'slack', 'teams', 'webhook'].includes(form.channel));
const availableVariables = computed(() => {
  const event = store.templateEvents.find((item) => item.value === form.event_key);
  return event?.variables || form.available_variables || [];
});
const channelHelp = computed(() => {
  const channel = store.templateChannels.find((item) => item.value === form.channel);
  return channel ? `${channel.label} template content and delivery settings.` : 'Select a delivery channel.';
});

onMounted(async () => {
  await store.fetchTemplates({ per_page: 1 });
  if (isEdit.value) {
    const template = await store.fetchTemplate(route.params.id);
    Object.assign(form, {
      name: template.name,
      event_key: template.event_key,
      channel: template.channel,
      locale: template.locale || 'en',
      subject: template.subject || '',
      body: template.body || '',
      priority: template.priority || 'normal',
      is_active: !!template.is_active,
      is_system: !!template.is_system,
      available_variables: template.available_variables || [],
      change_summary: '',
    });
  } else if (store.templateEvents[0]) {
    form.event_key = store.templateEvents[0].value;
  }
});

watch(
  () => form.event_key,
  () => {
    form.available_variables = availableVariables.value;
  }
);

function appendVariable(variable) {
  form.body = `${form.body || ''}{{${variable}}}`;
}

async function save() {
  formError.value = '';
  store.successMessage = null;
  try {
    const payload = {
      ...form,
      available_variables: availableVariables.value,
    };
    const saved = await store.saveTemplate(payload, isEdit.value ? route.params.id : null);
    store.successMessage = 'Template saved.';
    if (!isEdit.value) {
      await router.push({ name: 'notifications.templates.edit', params: { id: saved.uuid } });
    }
  } catch (error) {
    formError.value = error?.response?.data?.message || store.error || 'Unable to save';
  }
}

function goPreview() {
  router.push({ name: 'notifications.templates.preview', params: { id: route.params.id } });
}

function goVersions() {
  router.push({ name: 'notifications.templates.versions', params: { id: route.params.id } });
}

async function submitReview() {
  try {
    await store.submitTemplate(route.params.id, { comments: form.change_summary || 'Ready for review' });
  } catch (error) {
    formError.value = error?.response?.data?.message || store.error || 'Unable to submit';
  }
}

async function publish() {
  try {
    await store.publishTemplate(route.params.id);
  } catch (error) {
    formError.value = error?.response?.data?.message || store.error || 'Unable to publish';
  }
}
</script>

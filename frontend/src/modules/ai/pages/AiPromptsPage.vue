<template>
  <div>
    <PageHeader
      title="Prompt Manager"
      description="Versioned prompt templates for each AI feature."
    >
      <template #actions>
        <button
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="startCreate"
        >
          New prompt
        </button>
      </template>
    </PageHeader>
    <AiSubnav />

    <div v-if="store.error" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ store.error }}
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
      <input
        v-model="filters.search"
        type="search"
        placeholder="Search prompts…"
        class="rounded-lg border border-slate-300 px-3 py-2 text-sm"
        @keyup.enter="load"
      />
      <select v-model="filters.feature" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All features</option>
        <option v-for="feature in store.catalog.features || []" :key="feature.value" :value="feature.value">
          {{ feature.label }}
        </option>
      </select>
      <select v-model="filters.status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" @change="load">
        <option value="">All statuses</option>
        <option v-for="status in store.catalog.prompt_statuses || []" :key="status.value" :value="status.value">
          {{ status.label }}
        </option>
      </select>
    </div>

    <div v-if="showForm" class="mb-6 rounded-xl border border-slate-200 bg-white p-5">
      <h2 class="mb-4 text-sm font-semibold text-slate-900">{{ editingUuid ? 'Edit prompt' : 'Create prompt' }}</h2>
      <form class="grid gap-3 md:grid-cols-2" @submit.prevent="save">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Name</label>
          <input v-model="form.name" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Feature</label>
          <select v-model="form.feature" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="feature in store.catalog.features || []" :key="feature.value" :value="feature.value">
              {{ feature.label }}
            </option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-xs font-medium text-slate-600">System prompt</label>
          <textarea v-model="form.system_prompt" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-xs font-medium text-slate-600">User template</label>
          <textarea v-model="form.user_template" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
        </div>
        <div class="md:col-span-2 flex gap-2">
          <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white" :disabled="store.saving">
            Save
          </button>
          <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm" @click="showForm = false">
            Cancel
          </button>
        </div>
      </form>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white">
      <table class="min-w-full text-left text-sm">
        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
          <tr>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Feature</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Version</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="!store.prompts.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">No prompts found.</td>
          </tr>
          <tr v-for="prompt in store.prompts" :key="prompt.uuid" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <p class="font-medium text-slate-900">{{ prompt.name }}</p>
              <p class="text-xs text-slate-500">{{ prompt.key }}</p>
            </td>
            <td class="px-4 py-3">{{ prompt.feature_label || prompt.feature }}</td>
            <td class="px-4 py-3">{{ prompt.status_label || prompt.status }}</td>
            <td class="px-4 py-3">v{{ prompt.version }}</td>
            <td class="px-4 py-3 text-right">
              <div class="flex justify-end gap-2">
                <button class="text-xs font-medium text-brand-700" @click="edit(prompt)">Edit</button>
                <button
                  v-if="prompt.status !== 'published'"
                  class="text-xs font-medium text-emerald-700"
                  @click="publish(prompt)"
                >
                  Publish
                </button>
                <button class="text-xs font-medium text-rose-600" @click="remove(prompt)">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import AiSubnav from '@/modules/ai/components/AiSubnav.vue';
import { useAiStore } from '@/modules/ai/stores/ai';

const store = useAiStore();
const showForm = ref(false);
const editingUuid = ref(null);
const filters = reactive({ search: '', feature: '', status: '' });
const form = reactive({
  name: '',
  feature: 'chat_assistant',
  system_prompt: '',
  user_template: '',
});

function startCreate() {
  editingUuid.value = null;
  form.name = '';
  form.feature = 'chat_assistant';
  form.system_prompt = '';
  form.user_template = '';
  showForm.value = true;
}

function edit(prompt) {
  editingUuid.value = prompt.uuid;
  form.name = prompt.name;
  form.feature = prompt.feature;
  form.system_prompt = prompt.system_prompt || '';
  form.user_template = prompt.user_template || '';
  showForm.value = true;
}

async function load() {
  await store.fetchPrompts({ ...filters });
}

async function save() {
  const payload = { ...form };
  if (editingUuid.value) {
    await store.updatePrompt(editingUuid.value, payload);
  } else {
    await store.createPrompt(payload);
  }
  showForm.value = false;
  await load();
}

async function publish(prompt) {
  await store.publishPrompt(prompt.uuid);
  await load();
}

async function remove(prompt) {
  if (!window.confirm(`Delete prompt “${prompt.name}”?`)) return;
  await store.deletePrompt(prompt.uuid);
  await load();
}

onMounted(async () => {
  await store.fetchCatalog();
  await load();
});
</script>

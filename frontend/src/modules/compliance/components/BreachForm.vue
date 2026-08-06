<template>
  <form class="space-y-5" @submit.prevent="onSubmit">
    <div
      v-if="error"
      class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ error }}
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Company</label>
        <select v-model="form.company_id" class="input" required>
          <option value="" disabled>Select company</option>
          <option v-for="company in companies" :key="company.uuid" :value="company.uuid">
            {{ company.company_name }}
          </option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Title</label>
        <input v-model="form.title" type="text" class="input" required maxlength="255" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Breach type</label>
        <select v-model="form.breach_type" class="input" required>
          <option value="unauthorized_access">Unauthorized Access</option>
          <option value="data_loss">Data Loss</option>
          <option value="ransomware">Ransomware</option>
          <option value="phishing">Phishing</option>
          <option value="insider_threat">Insider Threat</option>
          <option value="misconfiguration">Misconfiguration</option>
          <option value="third_party">Third Party</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Severity</label>
        <select v-model="form.severity" class="input">
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="critical">Critical</option>
        </select>
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Discovered at</label>
        <input v-model="form.discovered_at" type="datetime-local" class="input" />
      </div>
      <div>
        <label class="mb-1 block text-sm font-medium text-slate-700">Occurred at</label>
        <input v-model="form.occurred_at" type="datetime-local" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Description</label>
        <textarea v-model="form.description" rows="4" class="input" />
      </div>
      <div class="md:col-span-2">
        <label class="mb-1 block text-sm font-medium text-slate-700">Affected data categories</label>
        <input
          v-model="categoriesInput"
          type="text"
          class="input"
          placeholder="email, name, phone (comma separated)"
        />
      </div>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.personal_data_involved" type="checkbox" class="rounded border-slate-300" />
        Personal data involved
      </label>
      <label class="flex items-center gap-2 text-sm text-slate-700">
        <input v-model="form.special_category_data" type="checkbox" class="rounded border-slate-300" />
        Special category data
      </label>
    </div>

    <div class="flex justify-end gap-3">
      <button
        type="button"
        class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Saving...' : 'Report incident' }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';

defineProps({
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
});

const emit = defineEmits(['submit', 'cancel']);

const companies = ref([]);
const categoriesInput = ref('email, name');
const form = reactive({
  company_id: '',
  title: '',
  description: '',
  breach_type: 'unauthorized_access',
  severity: 'medium',
  discovered_at: '',
  occurred_at: '',
  personal_data_involved: true,
  special_category_data: false,
});

onMounted(async () => {
  try {
    const { data } = await companyService.list({ per_page: 100, status: 'active' });
    companies.value = data.data?.companies?.items ?? [];
  } catch {
    companies.value = [];
  }
});

function onSubmit() {
  emit('submit', {
    ...form,
    discovered_at: form.discovered_at || undefined,
    occurred_at: form.occurred_at || undefined,
    affected_data_categories: categoriesInput.value
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean),
  });
}
</script>


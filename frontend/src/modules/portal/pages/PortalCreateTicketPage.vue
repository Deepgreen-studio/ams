<template>
  <div class="mx-auto max-w-2xl">
    <h1 class="text-2xl font-semibold text-slate-900">Submit a support ticket</h1>
    <p class="mt-1 text-sm text-slate-500">Describe your issue and our team will respond shortly.</p>

    <form class="mt-6 space-y-4 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="submit">
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Subject</label>
        <input v-model="form.subject" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Category</label>
          <select v-model="form.category" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="option in categories" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium text-slate-600">Priority</label>
          <select v-model="form.priority" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option v-for="option in priorities" :key="option.value" :value="option.value">{{ option.label }}</option>
          </select>
        </div>
      </div>
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-600">Description</label>
        <textarea
          v-model="form.description"
          required
          rows="8"
          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
          placeholder="What happened? Steps to reproduce, expected result…"
        />
      </div>
      <p v-if="error" class="text-sm text-rose-600">{{ error }}</p>
      <div class="flex justify-end gap-2">
        <RouterLink :to="{ name: 'portal.tickets.index' }" class="rounded-lg border px-4 py-2 text-sm">Cancel</RouterLink>
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Submitting…' : 'Submit ticket' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import { categoryOptions, priorityOptions } from '@/modules/support/utils/ticketOptions';
import { usePortalSupportStore } from '@/modules/portal/stores/portalSupport';

const store = usePortalSupportStore();
const router = useRouter();
const error = ref('');

const form = reactive({
  subject: '',
  description: '',
  category: 'customer_support',
  priority: 'medium',
});

const categories = computed(() => store.profile?.categories?.length ? store.profile.categories : categoryOptions);
const priorities = computed(() => store.profile?.priorities?.length ? store.profile.priorities : priorityOptions);

onMounted(() => store.fetchProfile().catch(() => {}));

async function submit() {
  error.value = '';
  try {
    const ticket = await store.createTicket({ ...form });
    await router.push({ name: 'portal.tickets.show', params: { id: ticket.uuid } });
  } catch (err) {
    error.value = err?.response?.data?.message || store.error || 'Unable to submit';
  }
}
</script>

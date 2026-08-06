<template>
  <div>
    <PageHeader
      title="Affected users"
      description="Maintain the list of individuals impacted by this breach."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'compliance.breaches.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to incident
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

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

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5">
      <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-900">
          {{ store.current?.breach_number || 'Incident' }} · {{ users.length }} users
        </h2>
        <button
          type="button"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50"
          @click="addRow"
        >
          Add user
        </button>
      </div>

      <div class="space-y-3">
        <div
          v-for="(user, index) in users"
          :key="index"
          class="grid gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-3"
        >
          <input v-model="user.name" type="text" class="input" placeholder="Name" />
          <input v-model="user.email" type="email" class="input" placeholder="Email" />
          <div class="flex gap-2">
            <input v-model="user.user_id" type="text" class="input" placeholder="User ID (optional)" />
            <button
              type="button"
              class="rounded-lg px-3 text-sm text-rose-700 hover:bg-rose-50"
              @click="users.splice(index, 1)"
            >
              Remove
            </button>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
          Data categories
        </label>
        <input
          v-model="categoriesInput"
          type="text"
          class="input"
          placeholder="email, name, phone"
        />
      </div>

      <div class="mt-4 flex justify-end">
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
          @click="onSave"
        >
          {{ store.saving ? 'Saving...' : 'Save affected users' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const route = useRoute();
const store = useDataBreachStore();
const users = ref([]);
const categoriesInput = ref('');

onMounted(async () => {
  await store.fetchBreach(route.params.id);
  users.value = (store.current?.affected_users || []).map((user) => ({
    name: user.name || '',
    email: user.email || '',
    user_id: user.user_id || '',
  }));
  if (!users.value.length) addRow();
  categoriesInput.value = (store.current?.affected_data_categories || []).join(', ');
});

function addRow() {
  users.value.push({ name: '', email: '', user_id: '' });
}

async function onSave() {
  await store.updateAffectedUsers(route.params.id, {
    affected_users: users.value.filter((user) => user.email || user.name),
    affected_data_categories: categoriesInput.value
      .split(',')
      .map((item) => item.trim())
      .filter(Boolean),
  });
  await store.fetchBreach(route.params.id);
}
</script>


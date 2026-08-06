<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Assignment type</label>
      <select v-model="form.type" class="input" required>
        <option v-for="option in assignmentTypeOptions" :key="option.value" :value="option.value">
          {{ option.label }}
        </option>
      </select>
    </div>

    <div v-if="form.type === 'department'">
      <label class="mb-1 block text-sm font-medium text-slate-700">Department</label>
      <select v-model="form.department_id" class="input" required>
        <option value="" disabled>Select department</option>
        <option v-for="department in departments" :key="department.uuid" :value="department.uuid">
          {{ department.name }}
        </option>
      </select>
    </div>

    <div v-if="form.type === 'team'">
      <label class="mb-1 block text-sm font-medium text-slate-700">Team</label>
      <select v-model="form.team_id" class="input" required>
        <option value="" disabled>Select team</option>
        <option v-for="team in teams" :key="team.uuid" :value="team.uuid">
          {{ team.name }}
        </option>
      </select>
    </div>

    <div v-if="form.type === 'agent' || form.type === 'manual'">
      <label class="mb-1 block text-sm font-medium text-slate-700">Agent</label>
      <select v-model="form.assigned_to" class="input" required>
        <option value="" disabled>Select agent</option>
        <option v-for="agent in agents" :key="agent.uuid" :value="agent.uuid">
          {{ agent.full_name }} ({{ agent.email }})
        </option>
      </select>
    </div>

    <div v-if="form.type === 'auto'" class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
      Auto assignment uses round-robin across support agents and managers.
    </div>

    <div>
      <label class="mb-1 block text-sm font-medium text-slate-700">Comments</label>
      <textarea v-model="form.comments" rows="3" class="input" placeholder="Optional assignment note" />
    </div>

    <div class="flex justify-end gap-2">
      <button
        v-if="showCancel"
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
        {{ loading ? 'Assigning...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import { assignmentTypeOptions } from '@/modules/support/utils/ticketOptions';

const props = defineProps({
  companyId: { type: String, default: '' },
  agents: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  submitLabel: { type: String, default: 'Assign ticket' },
  showCancel: { type: Boolean, default: false },
  initialType: { type: String, default: 'agent' },
});

const emit = defineEmits(['submit', 'cancel']);

const departments = ref([]);
const teams = ref([]);
const form = reactive({
  type: props.initialType,
  assigned_to: '',
  department_id: '',
  team_id: '',
  comments: '',
});

watch(
  () => props.companyId,
  async (companyId) => {
    if (companyId) {
      await loadOrg(companyId);
    }
  }
);

onMounted(async () => {
  if (props.companyId) {
    await loadOrg(props.companyId);
  }
});

async function loadOrg(companyId) {
  try {
    const [deptRes, teamRes] = await Promise.all([
      companyService.listDepartments({ company: companyId, per_page: 100 }),
      companyService.listTeams({ company: companyId, per_page: 100 }),
    ]);
    departments.value = deptRes.data.data?.departments?.items ?? deptRes.data.data?.departments ?? [];
    teams.value = teamRes.data.data?.teams?.items ?? teamRes.data.data?.teams ?? [];
  } catch {
    departments.value = [];
    teams.value = [];
  }
}

function onSubmit() {
  const payload = {
    type: form.type,
    comments: form.comments || null,
  };

  if (form.type === 'department') {
    payload.department_id = form.department_id;
  } else if (form.type === 'team') {
    payload.team_id = form.team_id;
  } else if (form.type === 'agent' || form.type === 'manual') {
    payload.assigned_to = form.assigned_to;
  }

  emit('submit', payload);
}
</script>

<style scoped>
.input {
  width: 100%;
  border-radius: 0.5rem;
  border: 1px solid #cbd5e1;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  outline: none;
}
.input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.15);
}
</style>

<template>
  <form class="space-y-4" @submit.prevent="onSubmit">
    <p v-if="error" class="rounded-[12px] bg-rose-50 px-4 py-3 text-sm text-rose-700">
      {{ error }}
    </p>

    <div>
      <label class="mb-1.5 block text-sm font-medium text-slate-700">Assignment type</label>
      <SelectBox v-model="form.type" :options="assignmentTypeOptions" />
    </div>

    <div v-if="form.type === 'department'">
      <label class="mb-1.5 block text-sm font-medium text-slate-700">Department</label>
      <SelectBox
        v-model="form.department_id"
        placeholder="Select department"
        :options="departmentOptions"
      />
    </div>

    <div v-if="form.type === 'team'">
      <label class="mb-1.5 block text-sm font-medium text-slate-700">Team</label>
      <SelectBox v-model="form.team_id" placeholder="Select team" :options="teamOptions" />
    </div>

    <div v-if="form.type === 'agent' || form.type === 'manual'">
      <label class="mb-1.5 block text-sm font-medium text-slate-700">Agent</label>
      <SelectBox
        v-model="form.assigned_to"
        placeholder="Select agent"
        :options="agentOptions"
      />
    </div>

    <div
      v-if="form.type === 'auto'"
      class="rounded-[12px] bg-zinc-50 px-3 py-2 text-sm text-slate-600"
    >
      Auto assignment uses round-robin across support agents and managers.
    </div>

    <div>
      <label class="mb-1.5 block text-sm font-medium text-slate-700">Comments</label>
      <textarea
        v-model="form.comments"
        rows="3"
        class="w-full rounded-[12px] border border-zinc-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:border-brand-500 focus:ring-0"
        placeholder="Optional assignment note"
      />
    </div>

    <div class="flex justify-end gap-2">
      <button
        v-if="showCancel"
        type="button"
        class="h-10 rounded-[12px] border border-zinc-200 px-5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
      <button
        type="submit"
        class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
        :disabled="loading"
      >
        {{ loading ? 'Assigning...' : submitLabel }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { companyService } from '@/modules/companies/services/companyService';
import SelectBox from '@/modules/users/components/SelectBox.vue';
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

const departmentOptions = computed(() =>
  departments.value.map((department) => ({ value: department.uuid, label: department.name })),
);
const teamOptions = computed(() =>
  teams.value.map((team) => ({ value: team.uuid, label: team.name })),
);
const agentOptions = computed(() =>
  props.agents.map((agent) => ({
    value: agent.uuid,
    label: `${agent.full_name} (${agent.email})`,
  })),
);

watch(
  () => props.companyId,
  async (companyId) => {
    if (companyId) {
      await loadOrg(companyId);
    }
  },
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

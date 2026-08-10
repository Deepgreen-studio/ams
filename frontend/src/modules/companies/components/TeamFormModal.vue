<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4"
    @click.self="onCancel"
  >
    <div
      class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="team-form-title"
    >
      <h3 id="team-form-title" class="text-lg font-semibold text-slate-900">
        {{ isEdit ? 'Edit team' : 'Add team' }}
      </h3>
      <p class="mt-1 text-sm text-slate-600">
        {{ isEdit ? 'Update team details.' : 'Create a team under a department.' }}
      </p>

      <form class="mt-5 space-y-4" @submit.prevent="onSubmit">
        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
            Department
          </label>
          <SelectBox
            v-model="form.department_id"
            wrapper-class="w-full"
            size="lg"
            placeholder="Select department"
            :options="departmentOptions"
            :disabled="loading"
          />
        </div>

        <div>
          <label
            for="team-name"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >
            Team name
          </label>
          <input
            id="team-name"
            ref="nameInput"
            v-model="form.name"
            type="text"
            maxlength="255"
            required
            autocomplete="off"
            class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="e.g. Platform"
            :disabled="loading"
            @keydown.esc.prevent="onCancel"
          />
        </div>

        <div>
          <label
            for="team-description"
            class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
          >
            Description
            <span class="normal-case tracking-normal text-slate-400">(optional)</span>
          </label>
          <input
            id="team-description"
            v-model="form.description"
            type="text"
            maxlength="500"
            autocomplete="off"
            class="h-12 w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 text-sm text-slate-800 shadow-none outline-none placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            placeholder="Short description"
            :disabled="loading"
          />
        </div>

        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">
            Status
          </label>
          <SelectBox
            v-model="form.status"
            wrapper-class="w-full"
            size="lg"
            :options="statusOptions"
            :disabled="loading"
          />
        </div>

        <div class="flex justify-end gap-2 pt-1">
          <button
            type="button"
            class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
            :disabled="loading"
            @click="onCancel"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
            :disabled="loading || !form.name.trim() || !form.department_id"
          >
            {{ submitLabel }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  team: {
    type: Object,
    default: null,
  },
  departmentOptions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['submit', 'cancel']);

const nameInput = ref(null);
const form = reactive({
  department_id: '',
  name: '',
  description: '',
  status: 'active',
});

const statusOptions = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
];

const isEdit = computed(() => Boolean(props.team?.uuid || props.team?.id));

const submitLabel = computed(() => {
  if (props.loading) return 'Saving...';
  return isEdit.value ? 'Update team' : 'Add team';
});

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    form.department_id = props.team?.department?.uuid || '';
    form.name = props.team?.name || '';
    form.description = props.team?.description || '';
    form.status = props.team?.status || 'active';
    await nextTick();
    nameInput.value?.focus();
  },
);

function onCancel() {
  if (props.loading) return;
  emit('cancel');
}

function onSubmit() {
  const name = form.name.trim();
  if (!name || !form.department_id || props.loading) return;

  emit('submit', {
    department_id: form.department_id,
    name,
    description: form.description.trim() ? form.description.trim() : null,
    status: form.status || 'active',
  });
}
</script>

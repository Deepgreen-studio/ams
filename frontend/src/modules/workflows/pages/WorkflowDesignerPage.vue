<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'workflows.designer' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <form class="space-y-4" novalidate @submit.prevent="submit">
      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-5">
          <h2 class="text-base font-semibold text-slate-900">Workflow details</h2>
          <p class="mt-0.5 text-sm text-slate-500">
            Name the definition and choose how it should behave.
          </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Name</span>
            <input
              v-model="form.name"
              type="text"
              placeholder="Workflow name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
              :class="fieldClass('name')"
            />
            <p v-if="fieldErrors.name" class="mt-1.5 text-xs text-rose-600">{{ fieldErrors.name[0] }}</p>
          </label>

          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Type</span>
            <SelectBox
              v-model="form.type"
              wrapper-class="w-full"
              :options="typeOptions"
              :error="Boolean(fieldErrors.type)"
            />
            <p v-if="fieldErrors.type" class="mt-1.5 text-xs text-rose-600">{{ fieldErrors.type[0] }}</p>
          </label>

          <label class="block text-sm md:col-span-2">
            <span class="mb-1.5 block font-medium text-slate-700">Description</span>
            <textarea
              v-model="form.description"
              rows="3"
              placeholder="Optional description"
              class="w-full rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>

          <label
            class="inline-flex items-center gap-2.5 rounded-[12px] border border-zinc-200 px-3.5 py-2.5 text-sm text-slate-700"
          >
            <input
              v-model="form.is_enabled"
              type="checkbox"
              class="rounded border-zinc-300 text-brand-600 focus:ring-brand-500"
            />
            Enabled
          </label>
        </div>
      </section>

      <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
        <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Visual stage canvas</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              Drag cards to position stages. Select a stage to edit details.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="stepType in store.catalog.step_types"
              :key="stepType.value"
              type="button"
              class="inline-flex h-9 items-center rounded-[12px] border border-zinc-200 bg-white px-3 text-xs font-medium text-slate-700 transition hover:bg-zinc-50"
              @click="addStep(stepType.value)"
            >
              + {{ stepType.label }}
            </button>
          </div>
        </div>

        <div
          ref="canvasRef"
          class="relative h-[420px] overflow-auto rounded-[12px] border border-dashed border-zinc-200 bg-zinc-50/60"
          :class="fieldErrors.steps ? 'border-rose-300 bg-rose-50/30' : ''"
          @dragover.prevent
          @drop.prevent="onCanvasDrop"
        >
          <div
            v-if="!form.steps.length"
            class="pointer-events-none absolute inset-0 flex items-center justify-center px-6 text-center text-sm text-slate-500"
          >
            Add stages with the buttons above to start designing this workflow.
          </div>

          <div
            v-for="(step, index) in form.steps"
            :key="step._id"
            class="absolute w-48 cursor-grab rounded-[12px] bg-white p-3.5 shadow-sm ring-1 transition active:cursor-grabbing"
            :class="
              selectedIndex === index
                ? 'ring-2 ring-brand-500 ring-offset-1'
                : 'ring-zinc-100 hover:ring-zinc-200'
            "
            :style="{ left: `${step.position_x}px`, top: `${step.position_y}px` }"
            draggable="true"
            @dragstart="onDragStart($event, index)"
            @click="selectedIndex = index"
          >
            <div class="mb-2 flex items-start justify-between gap-2">
              <span
                class="inline-flex max-w-[8.5rem] truncate rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                :class="stepTypeBadgeClass(step.step_type)"
              >
                {{ step.step_type }}
              </span>

              <div class="relative shrink-0" @click.stop>
                <button
                  type="button"
                  class="inline-flex h-7 w-7 items-center justify-center rounded-[10px] text-slate-400 transition hover:bg-zinc-100 hover:text-slate-700"
                  :aria-expanded="openMenuId === step._id"
                  aria-haspopup="menu"
                  aria-label="Stage actions"
                  @click="toggleMenu(step._id)"
                >
                  <EllipsisVerticalIcon class="h-4 w-4" />
                </button>

                <div
                  v-if="openMenuId === step._id"
                  class="absolute right-0 top-8 z-30 w-36 overflow-hidden rounded-[12px] bg-white py-1 shadow-lg ring-1 ring-zinc-100"
                  role="menu"
                >
                  <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-zinc-50"
                    role="menuitem"
                    @click="selectStep(index)"
                  >
                    <PencilSquareIcon class="h-4 w-4 text-slate-400" />
                    Edit
                  </button>
                  <button
                    type="button"
                    class="flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm text-red-600 transition hover:bg-red-50"
                    role="menuitem"
                    @click="removeStep(index)"
                  >
                    <TrashIcon class="h-4 w-4 text-red-500" />
                    Remove
                  </button>
                </div>
              </div>
            </div>

            <p class="truncate text-sm font-semibold text-slate-900">{{ step.name }}</p>
            <p class="mt-1 truncate text-xs text-slate-500">{{ step.step_key }}</p>
          </div>
        </div>
        <p v-if="fieldErrors.steps" class="mt-2 text-xs text-rose-600">{{ fieldErrors.steps[0] }}</p>
      </section>

      <section
        v-if="selectedStep"
        class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8"
      >
        <div class="mb-5 flex items-center justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-slate-900">Stage settings</h2>
            <p class="mt-0.5 text-sm text-slate-500">
              Configure transitions, approvers, and timeouts for the selected stage.
            </p>
          </div>
          <button
            type="button"
            class="rounded-[12px] border border-rose-200 px-3.5 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50"
            @click="removeSelected"
          >
            Remove stage
          </button>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Name</span>
            <input
              v-model="selectedStep.name"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Step key</span>
            <input
              v-model="selectedStep.step_key"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 font-mono text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Type</span>
            <SelectBox
              v-model="selectedStep.step_type"
              wrapper-class="w-full"
              :options="stepTypeOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Next step keys</span>
            <SelectBox
              v-model="nextStepKeys"
              multiple
              wrapper-class="w-full"
              placeholder="Select next steps"
              :options="nextStepKeyOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">On approve →</span>
            <SelectBox
              v-model="onApproveStepKey"
              wrapper-class="w-full"
              placeholder="Select next step"
              :options="transitionStepOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">On reject →</span>
            <SelectBox
              v-model="onRejectStepKey"
              wrapper-class="w-full"
              placeholder="Select next step"
              :options="transitionStepOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Approver roles</span>
            <SelectBox
              v-model="approverRoles"
              multiple
              wrapper-class="w-full"
              placeholder="Select approver roles"
              :options="roleOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Timeout (minutes)</span>
            <input
              v-model.number="selectedStep.config.timeout_minutes"
              type="number"
              min="1"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Escalate to role</span>
            <SelectBox
              v-model="escalateRole"
              wrapper-class="w-full"
              placeholder="No escalation"
              :options="escalateRoleOptions"
            />
          </label>
          <label class="block text-sm">
            <span class="mb-1.5 block font-medium text-slate-700">Approvals required</span>
            <input
              v-model.number="selectedStep.config.approvals_required"
              type="number"
              min="1"
              class="h-10 w-full rounded-[12px] border border-zinc-200 px-3.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-0"
            />
          </label>
        </div>
      </section>

      <div class="flex flex-wrap gap-3">
        <button
          type="submit"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving…' : isEdit ? 'Update workflow' : 'Create workflow' }}
        </button>
        <button
          v-if="isEdit"
          type="button"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
          :disabled="store.saving"
          @click="publish"
        >
          Publish
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import {
  EllipsisVerticalIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import SelectBox from '@/modules/users/components/SelectBox.vue';
import { useRolesStore } from '@/modules/roles/stores/roles';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const rolesStore = useRolesStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const canvasRef = ref(null);
const selectedIndex = ref(0);
const dragIndex = ref(null);
const openMenuId = ref(null);
const fieldErrors = ref({});
let uid = 0;

const isEdit = computed(() => Boolean(route.params.id));

const form = reactive({
  name: '',
  description: '',
  type: 'approval',
  is_enabled: true,
  steps: [],
});

const typeOptions = computed(() =>
  store.catalog.types.map((item) => ({ value: item.value, label: item.label })),
);

const stepTypeOptions = computed(() =>
  store.catalog.step_types.map((item) => ({ value: item.value, label: item.label })),
);

const roleOptions = computed(() =>
  (rolesStore.roles || []).map((role) => ({
    value: role.name,
    label: role.display_name || role.name,
  })),
);

const escalateRoleOptions = computed(() => [
  { value: '', label: 'No escalation' },
  ...roleOptions.value,
]);

const selectedStep = computed(() => form.steps[selectedIndex.value] || null);

const transitionStepOptions = computed(() => {
  const currentKey = selectedStep.value?.step_key;
  const steps = form.steps
    .filter((step) => step.step_key && step.step_key !== currentKey)
    .map((step) => ({
      value: step.step_key,
      label: `${step.name || step.step_key} (${step.step_key})`,
    }));

  return [{ value: '', label: 'None' }, ...steps];
});

const nextStepKeyOptions = computed(() =>
  transitionStepOptions.value.filter((option) => option.value !== ''),
);

const onApproveStepKey = computed({
  get: () => selectedStep.value?.on_approve_step_key ?? '',
  set: (value) => {
    if (!selectedStep.value) return;
    selectedStep.value.on_approve_step_key = value || '';
  },
});

const onRejectStepKey = computed({
  get: () => selectedStep.value?.on_reject_step_key ?? '',
  set: (value) => {
    if (!selectedStep.value) return;
    selectedStep.value.on_reject_step_key = value || '';
  },
});

const nextStepKeys = computed({
  get: () => selectedStep.value?.next_step_keys || [],
  set: (value) => {
    if (!selectedStep.value) return;
    selectedStep.value.next_step_keys = Array.isArray(value) ? value : [];
  },
});

const approverRoles = computed({
  get: () => selectedStep.value?.config?.approver_roles || [],
  set: (value) => {
    if (!selectedStep.value) return;
    if (!selectedStep.value.config) {
      selectedStep.value.config = {};
    }
    selectedStep.value.config.approver_roles = Array.isArray(value) ? value : [];
  },
});

const escalateRole = computed({
  get: () => selectedStep.value?.config?.escalate_to_role || '',
  set: (value) => {
    if (!selectedStep.value) return;
    if (!selectedStep.value.config) {
      selectedStep.value.config = {};
    }
    selectedStep.value.config.escalate_to_role = value || '';
  },
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message, 'Validation Failed');
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

function fieldClass(field) {
  return fieldErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
}

function stepTypeBadgeClass(stepType) {
  const map = {
    start: 'bg-emerald-50 text-emerald-700',
    approval: 'bg-brand-50 text-brand-700',
    task: 'bg-sky-50 text-sky-700',
    condition: 'bg-amber-50 text-amber-700',
    parallel_gateway: 'bg-violet-50 text-violet-700',
    end: 'bg-zinc-100 text-slate-700',
  };
  return map[stepType] || 'bg-zinc-100 text-slate-600';
}

function makeStep(stepType, overrides = {}) {
  uid += 1;
  const key = `${stepType}_${uid}`;
  return {
    _id: `local-${uid}`,
    name: stepType.replace('_', ' '),
    step_key: key,
    step_type: stepType,
    sort_order: form.steps.length,
    position_x: 40 + (form.steps.length % 4) * 200,
    position_y: 40 + Math.floor(form.steps.length / 4) * 120,
    config: {
      approver_roles: [],
      timeout_minutes: 1440,
      escalate_to_role: '',
      approvals_required: 1,
    },
    next_step_keys: [],
    on_approve_step_key: '',
    on_reject_step_key: '',
    is_required: true,
    ...overrides,
  };
}

function addStep(stepType) {
  form.steps.push(makeStep(stepType));
  selectedIndex.value = form.steps.length - 1;
  closeMenu();
}

function selectStep(index) {
  selectedIndex.value = index;
  closeMenu();
}

function removeStep(index) {
  closeMenu();
  form.steps.splice(index, 1);
  selectedIndex.value = Math.max(0, Math.min(selectedIndex.value, form.steps.length - 1));
}

function removeSelected() {
  if (selectedIndex.value < 0) return;
  removeStep(selectedIndex.value);
}

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id;
}

function closeMenu() {
  openMenuId.value = null;
}

function onDragStart(event, index) {
  dragIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
  closeMenu();
}

function onCanvasDrop(event) {
  if (dragIndex.value === null || !canvasRef.value) return;
  const rect = canvasRef.value.getBoundingClientRect();
  const x = Math.max(0, event.clientX - rect.left + canvasRef.value.scrollLeft - 80);
  const y = Math.max(0, event.clientY - rect.top + canvasRef.value.scrollTop - 24);
  form.steps[dragIndex.value].position_x = Math.round(x);
  form.steps[dragIndex.value].position_y = Math.round(y);
  selectedIndex.value = dragIndex.value;
  dragIndex.value = null;
}

function hydrate(workflow) {
  form.name = workflow.name || '';
  form.description = workflow.description || '';
  form.type = workflow.type || 'approval';
  form.is_enabled = Boolean(workflow.is_enabled);
  form.steps = (workflow.steps || []).map((step, index) => ({
    _id: step.uuid || `step-${index}`,
    name: step.name,
    step_key: step.step_key,
    step_type: step.step_type,
    sort_order: step.sort_order ?? index,
    position_x: step.position_x ?? index * 200,
    position_y: step.position_y ?? 120,
    config: {
      approver_roles: step.config?.approver_roles || [],
      timeout_minutes: step.config?.timeout_minutes ?? 1440,
      escalate_to_role: step.config?.escalate_to_role || '',
      approvals_required: step.config?.approvals_required ?? 1,
      ...step.config,
    },
    next_step_keys: step.next_step_keys || [],
    on_approve_step_key: step.on_approve_step_key || '',
    on_reject_step_key: step.on_reject_step_key || '',
    is_required: step.is_required !== false,
  }));
  selectedIndex.value = 0;
  fieldErrors.value = {};
}

function validate() {
  const next = {};

  if (!String(form.name || '').trim()) {
    next.name = ['The name field is required.'];
  }

  if (!String(form.type || '').trim()) {
    next.type = ['The type field is required.'];
  }

  if (!form.steps.length) {
    next.steps = ['Add at least one stage to the workflow canvas.'];
  } else {
    const hasStart = form.steps.some((step) => step.step_type === 'start');
    const hasEnd = form.steps.some((step) => step.step_type === 'end');
    if (!hasStart || !hasEnd) {
      next.steps = ['Workflow must include both a Start and an End stage.'];
    }
  }

  fieldErrors.value = next;
  return Object.keys(next).length === 0;
}

async function submit() {
  if (!validate()) {
    toast.error('Please fix the highlighted fields.', 'Validation Failed');
    return;
  }

  fieldErrors.value = {};

  const payload = {
    name: form.name,
    description: form.description,
    type: form.type,
    is_enabled: form.is_enabled,
    steps: form.steps.map((step, index) => ({
      name: step.name,
      step_key: step.step_key,
      step_type: step.step_type,
      sort_order: index,
      position_x: step.position_x,
      position_y: step.position_y,
      config: step.config,
      next_step_keys: step.next_step_keys,
      on_approve_step_key: step.on_approve_step_key || null,
      on_reject_step_key: step.on_reject_step_key || null,
      is_required: step.is_required,
    })),
  };

  try {
    const saved = await store.saveWorkflow(payload, isEdit.value ? route.params.id : null);
    if (!isEdit.value && saved?.uuid) {
      await router.push({ name: 'workflows.designer.edit', params: { id: saved.uuid } });
    }
  } catch {
    // Store sets error; toast watch handles display.
  }
}

async function publish() {
  try {
    await store.publishWorkflow(route.params.id);
  } catch {
    // Store sets error; toast watch handles display.
  }
}

function onDocumentClick() {
  closeMenu();
}

onMounted(async () => {
  document.addEventListener('click', onDocumentClick);
  await store.fetchCatalog();
  try {
    await rolesStore.fetchRoles({ per_page: 100, sort_by: 'name', sort_dir: 'asc', page: 1 });
  } catch {
    // Roles list is optional for designer usability; keep page usable if forbidden.
  }
  if (isEdit.value) {
    const workflow = await store.fetchWorkflow(route.params.id);
    hydrate(workflow);
  } else {
    form.steps = [
      makeStep('start', {
        name: 'Start',
        step_key: 'start',
        position_x: 40,
        position_y: 140,
        next_step_keys: ['manager_approval'],
      }),
      makeStep('approval', {
        name: 'Manager Approval',
        step_key: 'manager_approval',
        position_x: 280,
        position_y: 140,
        on_approve_step_key: 'end',
        on_reject_step_key: 'end',
        config: {
          approver_roles: ['manager', 'super-admin'],
          timeout_minutes: 1440,
          escalate_to_role: 'super-admin',
          approvals_required: 1,
        },
      }),
      makeStep('end', { name: 'End', step_key: 'end', position_x: 520, position_y: 140 }),
    ];
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
});
</script>

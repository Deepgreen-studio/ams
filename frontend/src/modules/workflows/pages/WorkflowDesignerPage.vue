<template>
  <div>
    <!-- <PageHeader
      :title="isEdit ? 'Edit Workflow' : 'Create Workflow'"
      description="Drag stages on the canvas. Configure approvers, timeouts, and transitions."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'workflows.designer' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'workflows.designer' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
    </Teleport>

    <WorkflowsSubnav />

    <form class="space-y-6" novalidate @submit.prevent="submit">
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="grid gap-4 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Name</span>
            <input
              v-model="form.name"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
              :class="fieldClass('name')"
            />
            <p v-if="fieldErrors.name" class="mt-1 text-xs text-rose-600">{{ fieldErrors.name[0] }}</p>
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Type</span>
            <select
              v-model="form.type"
              class="w-full rounded-lg border border-slate-300 px-3 py-2"
              :class="fieldClass('type')"
            >
              <option v-for="item in store.catalog.types" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
            <p v-if="fieldErrors.type" class="mt-1 text-xs text-rose-600">{{ fieldErrors.type[0] }}</p>
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block font-medium text-slate-700">Description</span>
            <textarea v-model="form.description" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-700">
            <input v-model="form.is_enabled" type="checkbox" class="rounded border-slate-300" />
            Enabled
          </label>
        </div>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 class="text-sm font-semibold text-slate-900">Visual stage canvas</h2>
            <p class="text-xs text-slate-500">Drag cards to position stages. Select a stage to edit details.</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="stepType in store.catalog.step_types"
              :key="stepType.value"
              type="button"
              class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50"
              @click="addStep(stepType.value)"
            >
              + {{ stepType.label }}
            </button>
          </div>
        </div>

        <div
          ref="canvasRef"
          class="relative h-[420px] overflow-auto rounded-xl border border-dashed border-slate-300 bg-slate-50"
          :class="fieldErrors.steps ? 'border-rose-300' : ''"
          @dragover.prevent
          @drop.prevent="onCanvasDrop"
        >
          <div
            v-for="(step, index) in form.steps"
            :key="step._id"
            class="absolute w-44 cursor-grab rounded-lg border bg-white p-3 active:cursor-grabbing"
            :class="selectedIndex === index ? 'border-brand-500 ring-2 ring-brand-200' : 'border-slate-200'"
            :style="{ left: `${step.position_x}px`, top: `${step.position_y}px` }"
            draggable="true"
            @dragstart="onDragStart($event, index)"
            @click="selectedIndex = index"
          >
            <p class="truncate text-xs font-semibold uppercase tracking-wide text-slate-500">{{ step.step_type }}</p>
            <p class="mt-1 truncate text-sm font-medium text-slate-900">{{ step.name }}</p>
            <p class="mt-1 truncate text-[11px] text-slate-400">{{ step.step_key }}</p>
          </div>
        </div>
        <p v-if="fieldErrors.steps" class="mt-2 text-xs text-rose-600">{{ fieldErrors.steps[0] }}</p>
      </section>

      <section v-if="selectedStep" class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-sm font-semibold text-slate-900">Stage settings</h2>
          <button type="button" class="text-sm text-rose-600 hover:underline" @click="removeSelected">Remove stage</button>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Name</span>
            <input v-model="selectedStep.name" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Step key</span>
            <input v-model="selectedStep.step_key" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Type</span>
            <select v-model="selectedStep.step_type" class="w-full rounded-lg border border-slate-300 px-3 py-2">
              <option v-for="item in store.catalog.step_types" :key="item.value" :value="item.value">{{ item.label }}</option>
            </select>
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Next step keys (comma)</span>
            <input v-model="nextKeysInput" class="w-full rounded-lg border border-slate-300 px-3 py-2" @change="applyNextKeys" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">On approve →</span>
            <input v-model="selectedStep.on_approve_step_key" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">On reject →</span>
            <input v-model="selectedStep.on_reject_step_key" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Approver roles (comma)</span>
            <input v-model="approverRolesInput" class="w-full rounded-lg border border-slate-300 px-3 py-2" @change="applyApproverRoles" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Timeout (minutes)</span>
            <input v-model.number="selectedStep.config.timeout_minutes" type="number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Escalate to role</span>
            <input v-model="selectedStep.config.escalate_to_role" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block font-medium text-slate-700">Approvals required</span>
            <input v-model.number="selectedStep.config.approvals_required" type="number" min="1" class="w-full rounded-lg border border-slate-300 px-3 py-2" />
          </label>
        </div>
      </section>

      <div class="flex flex-wrap gap-3">
        <button
          type="submit"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
          :disabled="store.saving"
        >
          {{ store.saving ? 'Saving...' : isEdit ? 'Update workflow' : 'Create workflow' }}
        </button>
        <button
          v-if="isEdit"
          type="button"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium hover:bg-slate-50"
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
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { useToast } from '@/composables/useToast';
import WorkflowsSubnav from '@/modules/workflows/components/WorkflowsSubnav.vue';
import { useWorkflowStore } from '@/modules/workflows/stores/workflow';

const store = useWorkflowStore();
const route = useRoute();
const router = useRouter();
const toast = useToast();
const canvasRef = ref(null);
const selectedIndex = ref(0);
const dragIndex = ref(null);
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

const selectedStep = computed(() => form.steps[selectedIndex.value] || null);

const nextKeysInput = computed({
  get: () => (selectedStep.value?.next_step_keys || []).join(', '),
  set: () => {},
});

const approverRolesInput = computed({
  get: () => (selectedStep.value?.config?.approver_roles || []).join(', '),
  set: () => {},
});

watch(
  () => store.error,
  (message) => {
    if (message) {
      toast.error(message, 'Validation Failed');
    }
  }
);

watch(
  () => store.successMessage,
  (message) => {
    if (message) {
      toast.success(message);
    }
  }
);

function fieldClass(field) {
  return fieldErrors.value?.[field] ? 'border-rose-400 focus:border-rose-500' : '';
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
}

function removeSelected() {
  if (selectedIndex.value < 0) return;
  form.steps.splice(selectedIndex.value, 1);
  selectedIndex.value = Math.max(0, selectedIndex.value - 1);
}

function onDragStart(event, index) {
  dragIndex.value = index;
  event.dataTransfer.effectAllowed = 'move';
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

function applyNextKeys(event) {
  if (!selectedStep.value) return;
  selectedStep.value.next_step_keys = String(event.target.value || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
}

function applyApproverRoles(event) {
  if (!selectedStep.value) return;
  selectedStep.value.config.approver_roles = String(event.target.value || '')
    .split(',')
    .map((item) => item.trim())
    .filter(Boolean);
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

onMounted(async () => {
  await store.fetchCatalog();
  if (isEdit.value) {
    const workflow = await store.fetchWorkflow(route.params.id);
    hydrate(workflow);
  } else {
    form.steps = [
      makeStep('start', { name: 'Start', step_key: 'start', position_x: 40, position_y: 140, next_step_keys: ['manager_approval'] }),
      makeStep('approval', {
        name: 'Manager Approval',
        step_key: 'manager_approval',
        position_x: 280,
        position_y: 140,
        on_approve_step_key: 'end',
        on_reject_step_key: 'end',
        config: { approver_roles: ['manager', 'super-admin'], timeout_minutes: 1440, escalate_to_role: 'super-admin', approvals_required: 1 },
      }),
      makeStep('end', { name: 'End', step_key: 'end', position_x: 520, position_y: 140 }),
    ];
  }
});
</script>

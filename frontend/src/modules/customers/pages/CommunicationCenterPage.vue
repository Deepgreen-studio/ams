<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'customers.show', params: { id: route.params.id } }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back
      </RouterLink>
      <button
        v-if="primaryAction"
        type="button"
        class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
        @click="primaryAction.onClick"
      >
        {{ primaryAction.label }}
      </button>
    </Teleport>

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error && !anyFormOpen"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-[12px] bg-white px-4 py-3 ring-1 ring-zinc-100"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ card.label }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-[12px] px-4 py-2 text-sm font-medium transition"
        :class="
          activeTab === tab.id
            ? 'bg-brand-600 text-white'
            : 'border border-zinc-200 text-slate-700 hover:bg-zinc-50'
        "
        @click="switchTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div
      v-if="store.loading && ['timeline', 'activity', 'calendar'].includes(activeTab)"
      class="h-40 animate-pulse rounded-[12px] bg-slate-100"
    />

    <!-- Timeline -->
    <div
      v-else-if="activeTab === 'timeline'"
      class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100"
    >
      <h3 class="text-base font-semibold text-slate-900">Communication timeline</h3>
      <EmptyState
        v-if="!store.timeline.length"
        title="No timeline entries"
        description="Notes, tasks, and communications will appear here."
        class="py-10"
      />
      <ol v-else class="relative mt-6 space-y-5 border-l border-zinc-100 pl-6">
        <li
          v-for="(item, index) in store.timeline"
          :key="`${item.source}-${item.uuid}-${index}`"
          class="relative"
        >
          <span
            class="absolute -left-[1.55rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
          />
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
              <time class="shrink-0 text-xs text-slate-500">{{ formatDate(item.occurred_at) }}</time>
            </div>
            <p class="mt-1 text-xs capitalize text-slate-500">
              {{ item.source }} · {{ item.type }}
            </p>
            <p v-if="item.summary" class="mt-1 text-sm text-slate-600">{{ item.summary }}</p>
          </div>
        </li>
      </ol>
    </div>

    <!-- Notes -->
    <div
      v-else-if="activeTab === 'notes'"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="type in noteFilters"
            :key="type.value"
            type="button"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium transition"
            :class="
              noteTypeFilter === type.value
                ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-200'
                : 'bg-zinc-50 text-slate-600 hover:bg-zinc-100'
            "
            @click="filterNotes(type.value)"
          >
            {{ type.label }}
          </button>
        </div>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="noteFormOpen = true"
        >
          Add note
        </button>
      </div>

      <div v-if="store.loading" class="space-y-3 p-5">
        <div v-for="n in 3" :key="n" class="h-16 animate-pulse rounded-[12px] bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.notes.length"
        title="No notes yet"
        description="Add a note or meeting summary for this customer."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="noteFormOpen = true"
          >
            Add note
          </button>
        </template>
      </EmptyState>
      <ul v-else class="divide-y divide-zinc-100">
        <li
          v-for="note in store.notes"
          :key="note.uuid"
          class="flex flex-wrap items-start justify-between gap-3 px-5 py-4"
        >
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <p class="text-sm font-medium text-slate-900">
                {{ note.title || note.note_type_label || formatLabel(note.note_type) }}
              </p>
              <span
                v-if="note.is_pinned"
                class="inline-flex items-center rounded-full border border-amber-500 bg-white px-2 py-0.5 text-[11px] font-medium text-amber-700"
              >
                Pinned
              </span>
            </div>
            <p class="mt-1 text-xs capitalize text-slate-500">
              {{ formatLabel(note.note_type) }} · {{ formatDate(note.occurred_at || note.created_at) }}
            </p>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ note.body }}</p>
          </div>
          <button
            type="button"
            class="text-xs font-medium text-rose-700 hover:text-rose-800"
            @click="pendingDelete = { type: 'note', item: note }"
          >
            Delete
          </button>
        </li>
      </ul>
    </div>

    <!-- Tasks -->
    <div
      v-else-if="activeTab === 'tasks'"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
        <h3 class="text-base font-semibold text-slate-900">Tasks</h3>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="taskFormOpen = true"
        >
          Create task
        </button>
      </div>

      <div v-if="store.loading" class="space-y-3 p-5">
        <div v-for="n in 3" :key="n" class="h-14 animate-pulse rounded-[12px] bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.tasks.length"
        title="No tasks yet"
        description="Create a follow-up task or reminder for this customer."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="taskFormOpen = true"
          >
            Create task
          </button>
        </template>
      </EmptyState>
      <ul v-else class="divide-y divide-zinc-100">
        <li
          v-for="task in store.tasks"
          :key="task.uuid"
          class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
        >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900">{{ task.title }}</p>
            <p class="mt-1 text-xs text-slate-500">Due {{ formatDate(task.due_at) }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <TaskStatusBadge :status="task.status" />
              <TaskPriorityBadge :priority="task.priority" />
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            <button
              v-if="task.status !== 'completed'"
              type="button"
              class="rounded-[10px] border border-emerald-200 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
              :disabled="store.saving"
              @click="completeTask(task.uuid)"
            >
              Complete
            </button>
            <button
              type="button"
              class="rounded-[10px] border border-rose-200 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-50"
              @click="pendingDelete = { type: 'task', item: task }"
            >
              Delete
            </button>
          </div>
        </li>
      </ul>
    </div>

    <!-- Calendar / reminders -->
    <div
      v-else-if="activeTab === 'calendar'"
      class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100"
    >
      <div class="mb-5 flex flex-wrap items-end gap-3">
        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >From</label
          >
          <input
            v-model="calendarFrom"
            type="date"
            class="h-10 rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 focus:border-brand-500 focus:outline-none"
          />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >To</label
          >
          <input
            v-model="calendarTo"
            type="date"
            class="h-10 rounded-[12px] border border-zinc-200 px-3 text-sm text-slate-800 focus:border-brand-500 focus:outline-none"
          />
        </div>
        <button
          type="button"
          class="h-10 rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700"
          @click="loadCalendar"
        >
          Refresh
        </button>
      </div>

      <h3 class="text-base font-semibold text-slate-900">Reminder calendar</h3>
      <EmptyState
        v-if="!store.reminders.length"
        title="No reminders in this range"
        description="Tasks with reminder dates will show up here."
        class="py-10"
      />
      <ul v-else class="mt-4 divide-y divide-zinc-100 overflow-hidden rounded-[12px] bg-slate-50/60">
        <li v-for="item in store.reminders" :key="item.uuid" class="px-4 py-3.5">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
            <TaskPriorityBadge :priority="item.priority" />
          </div>
          <p class="mt-1 text-xs text-slate-500">
            Remind {{ formatDate(item.remind_at) }} · Due {{ formatDate(item.due_at) }}
          </p>
        </li>
      </ul>
    </div>

    <!-- Email / communications -->
    <div
      v-else-if="activeTab === 'emails'"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4">
        <div class="flex flex-wrap gap-2">
          <button
            v-for="type in commFilters"
            :key="type.value"
            type="button"
            class="rounded-[10px] px-3 py-1.5 text-xs font-medium transition"
            :class="
              commTypeFilter === type.value
                ? 'bg-brand-50 text-brand-700 ring-1 ring-brand-200'
                : 'bg-zinc-50 text-slate-600 hover:bg-zinc-100'
            "
            @click="filterCommunications(type.value)"
          >
            {{ type.label }}
          </button>
        </div>
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="commFormOpen = true"
        >
          Log communication
        </button>
      </div>

      <div v-if="store.loading" class="space-y-3 p-5">
        <div v-for="n in 3" :key="n" class="h-16 animate-pulse rounded-[12px] bg-slate-100" />
      </div>
      <EmptyState
        v-else-if="!store.communications.length"
        title="No communications yet"
        description="Log emails, calls, or meetings with this customer."
      >
        <template #action>
          <button
            type="button"
            class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
            @click="commFormOpen = true"
          >
            Log communication
          </button>
        </template>
      </EmptyState>
      <ul v-else class="divide-y divide-zinc-100">
        <li
          v-for="item in store.communications"
          :key="item.uuid"
          class="flex flex-wrap items-start justify-between gap-3 px-5 py-4"
        >
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900">
              {{ item.subject || item.type_label || formatLabel(item.type) }}
            </p>
            <p class="mt-1 text-xs capitalize text-slate-500">
              {{ formatLabel(item.type) }} · {{ formatLabel(item.direction) }} ·
              {{ formatDate(item.occurred_at) }}
            </p>
            <p v-if="item.body" class="mt-2 text-sm text-slate-700">{{ item.body }}</p>
          </div>
          <button
            type="button"
            class="text-xs font-medium text-rose-700 hover:text-rose-800"
            @click="pendingDelete = { type: 'communication', item }"
          >
            Delete
          </button>
        </li>
      </ul>
    </div>

    <!-- Activity -->
    <div
      v-else-if="activeTab === 'activity'"
      class="rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100"
    >
      <h3 class="text-base font-semibold text-slate-900">Activity timeline</h3>
      <EmptyState
        v-if="!store.activity.length"
        title="No activity yet"
        description="Create notes, tasks, or communications to populate this feed."
        class="py-10"
      />
      <ol v-else class="relative mt-6 space-y-5 border-l border-zinc-100 pl-6">
        <li v-for="(item, index) in store.activity" :key="item.id || index" class="relative">
          <span
            class="absolute -left-[1.55rem] top-1.5 h-3 w-3 rounded-full border-2 border-white bg-brand-500 ring-1 ring-brand-200"
          />
          <div class="rounded-[12px] bg-zinc-50 px-4 py-3.5">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <p class="text-sm font-medium text-slate-900">
                {{ item.description || item.event || 'Activity' }}
              </p>
              <time class="shrink-0 text-xs text-slate-500">{{ formatDate(item.created_at) }}</time>
            </div>
            <p class="mt-1 text-xs text-slate-500">
              {{ formatSubjectType(item.subject_type) }}
              <span v-if="item.causer?.full_name"> · {{ item.causer.full_name }}</span>
            </p>
          </div>
        </li>
      </ol>
    </div>

    <NoteFormModal
      :open="noteFormOpen"
      :loading="store.saving"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeNoteForm"
      @submit="submitNote"
    />

    <TaskFormModal
      :open="taskFormOpen"
      :loading="store.saving"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeTaskForm"
      @submit="submitTask"
    />

    <CommunicationFormModal
      :open="commFormOpen"
      :loading="store.saving"
      :errors="store.fieldErrors"
      :error="store.error || ''"
      @cancel="closeCommForm"
      @submit="submitCommunication"
    />

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      :title="deleteTitle"
      :message="deleteMessage"
      confirm-label="Delete"
      :loading="store.saving"
      @cancel="pendingDelete = null"
      @confirm="confirmDelete"
    />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CommunicationFormModal from '@/modules/customers/components/CommunicationFormModal.vue';
import NoteFormModal from '@/modules/customers/components/NoteFormModal.vue';
import TaskFormModal from '@/modules/customers/components/TaskFormModal.vue';
import TaskPriorityBadge from '@/modules/customers/components/TaskPriorityBadge.vue';
import TaskStatusBadge from '@/modules/customers/components/TaskStatusBadge.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCommunicationStore } from '@/modules/customers/stores/communication';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCommunicationStore();

const activeTab = ref('timeline');
const noteTypeFilter = ref('');
const commTypeFilter = ref('');
const calendarFrom = ref('');
const calendarTo = ref('');
const noteFormOpen = ref(false);
const taskFormOpen = ref(false);
const commFormOpen = ref(false);
const pendingDelete = ref(null);

const tabs = [
  { id: 'timeline', label: 'Timeline' },
  { id: 'notes', label: 'Notes' },
  { id: 'tasks', label: 'Tasks' },
  { id: 'calendar', label: 'Reminders' },
  { id: 'emails', label: 'Email & calls' },
  { id: 'activity', label: 'Activity' },
];

const noteFilters = [
  { value: '', label: 'All' },
  { value: 'general', label: 'Notes' },
  { value: 'internal', label: 'Internal' },
  { value: 'meeting', label: 'Meeting' },
];

const commFilters = [
  { value: '', label: 'All' },
  { value: 'email', label: 'Email' },
  { value: 'call', label: 'Calls' },
  { value: 'meeting', label: 'Meetings' },
];

const anyFormOpen = computed(
  () => noteFormOpen.value || taskFormOpen.value || commFormOpen.value,
);

const primaryAction = computed(() => {
  if (activeTab.value === 'notes') {
    return { label: 'Add note', onClick: () => (noteFormOpen.value = true) };
  }
  if (activeTab.value === 'tasks') {
    return { label: 'Create task', onClick: () => (taskFormOpen.value = true) };
  }
  if (activeTab.value === 'emails') {
    return { label: 'Log communication', onClick: () => (commFormOpen.value = true) };
  }
  return null;
});

const statCards = computed(() => {
  const stats = store.overview?.statistics || {};
  return [
    { label: 'Notes', value: stats.notes?.total ?? 0 },
    { label: 'Open tasks', value: (stats.tasks?.open ?? 0) + (stats.tasks?.in_progress ?? 0) },
    { label: 'Emails', value: stats.communications?.email ?? 0 },
    { label: 'Upcoming reminders', value: stats.tasks?.upcoming_reminders ?? 0 },
  ];
});

const deleteTitle = computed(() => {
  const type = pendingDelete.value?.type;
  if (type === 'note') return 'Delete note';
  if (type === 'task') return 'Delete task';
  if (type === 'communication') return 'Delete communication';
  return 'Delete';
});

const deleteMessage = computed(() => {
  const entry = pendingDelete.value;
  if (!entry) return '';
  if (entry.type === 'note') {
    return `Delete ${entry.item?.title || 'this note'}? You can restore it later if needed.`;
  }
  if (entry.type === 'task') {
    return `Delete ${entry.item?.title || 'this task'}? You can restore it later if needed.`;
  }
  return `Delete ${entry.item?.subject || 'this communication'}? You can restore it later if needed.`;
});

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  calendarFrom.value = toDateInput(start);
  calendarTo.value = toDateInput(end);
  await store.fetchOverview(route.params.id);
  await store.fetchTimeline({ customer: route.params.id });
});

async function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'timeline') await store.fetchTimeline({ customer: route.params.id });
  if (tab === 'notes') {
    await store.fetchNotes({
      customer: route.params.id,
      note_type: noteTypeFilter.value || undefined,
    });
  }
  if (tab === 'tasks') await store.fetchTasks({ customer: route.params.id });
  if (tab === 'calendar') await loadCalendar();
  if (tab === 'emails') {
    await store.fetchCommunications({
      customer: route.params.id,
      type: commTypeFilter.value || undefined,
    });
  }
  if (tab === 'activity') await store.fetchActivity({ customer: route.params.id });
}

async function filterNotes(type) {
  noteTypeFilter.value = type;
  await store.fetchNotes({ customer: route.params.id, note_type: type || undefined });
}

async function filterCommunications(type) {
  commTypeFilter.value = type;
  await store.fetchCommunications({ customer: route.params.id, type: type || undefined });
}

async function loadCalendar() {
  await store.fetchCalendar({
    customer: route.params.id,
    from: calendarFrom.value || undefined,
    to: calendarTo.value || undefined,
  });
}

function closeNoteForm() {
  if (store.saving) return;
  noteFormOpen.value = false;
  store.clearMessages();
}

function closeTaskForm() {
  if (store.saving) return;
  taskFormOpen.value = false;
  store.clearMessages();
}

function closeCommForm() {
  if (store.saving) return;
  commFormOpen.value = false;
  store.clearMessages();
}

async function submitNote(payload) {
  await store.createNote({
    customer_id: route.params.id,
    ...payload,
  });
  noteFormOpen.value = false;
  activeTab.value = 'notes';
  await store.fetchNotes({
    customer: route.params.id,
    note_type: noteTypeFilter.value || undefined,
  });
  await store.fetchOverview(route.params.id);
}

async function submitTask(payload) {
  await store.createTask({
    customer_id: route.params.id,
    title: payload.title,
    description: payload.description,
    priority: payload.priority,
    due_at: payload.due_at ? new Date(payload.due_at).toISOString() : null,
    remind_at: payload.remind_at ? new Date(payload.remind_at).toISOString() : null,
  });
  taskFormOpen.value = false;
  activeTab.value = 'tasks';
  await store.fetchTasks({ customer: route.params.id });
  await store.fetchOverview(route.params.id);
}

async function submitCommunication(payload) {
  await store.createCommunication({
    customer_id: route.params.id,
    type: payload.type,
    direction: payload.direction,
    subject: payload.subject,
    body: payload.body,
    duration_seconds: payload.duration_seconds,
    occurred_at: payload.occurred_at
      ? new Date(payload.occurred_at).toISOString()
      : new Date().toISOString(),
  });
  commFormOpen.value = false;
  activeTab.value = 'emails';
  await store.fetchCommunications({
    customer: route.params.id,
    type: commTypeFilter.value || undefined,
  });
  await store.fetchOverview(route.params.id);
}

async function completeTask(id) {
  await store.completeTask(id);
  await store.fetchTasks({ customer: route.params.id });
  await store.fetchOverview(route.params.id);
}

async function confirmDelete() {
  const entry = pendingDelete.value;
  if (!entry) return;

  if (entry.type === 'note') {
    await store.archiveNote(entry.item.uuid);
    await store.fetchNotes({
      customer: route.params.id,
      note_type: noteTypeFilter.value || undefined,
    });
  } else if (entry.type === 'task') {
    await store.archiveTask(entry.item.uuid);
    await store.fetchTasks({ customer: route.params.id });
  } else if (entry.type === 'communication') {
    await store.archiveCommunication(entry.item.uuid);
    await store.fetchCommunications({
      customer: route.params.id,
      type: commTypeFilter.value || undefined,
    });
  }

  pendingDelete.value = null;
  await store.fetchOverview(route.params.id);
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function formatLabel(value) {
  return String(value || '')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatSubjectType(value) {
  if (!value) return 'System';
  const parts = String(value).split('\\');
  return formatLabel(parts[parts.length - 1] || value);
}

function toDateInput(date) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}
</script>

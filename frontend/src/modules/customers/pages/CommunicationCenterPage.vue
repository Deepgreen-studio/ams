<template>
  <div>
    <!-- <PageHeader
      title="Communication center"
      :description="`Notes, tasks, emails, and reminders for ${customerName}.`"
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          :to="{ name: 'customers.show', params: { id: route.params.id } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
    </Teleport>

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

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div
        v-for="card in statCards"
        :key="card.label"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-2 text-2xl font-semibold text-slate-900">{{ card.value }}</p>
      </div>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        type="button"
        class="rounded-lg px-3 py-2 text-sm font-medium transition"
        :class="
          activeTab === tab.id
            ? 'bg-brand-600 text-white'
            : 'border border-slate-300 text-slate-700 hover:bg-slate-50'
        "
        @click="switchTab(tab.id)"
      >
        {{ tab.label }}
      </button>
    </div>

    <div
      v-if="store.loading && activeTab === 'timeline'"
      class="h-40 animate-pulse rounded-xl bg-slate-100"
    />

    <!-- Timeline -->
    <div
      v-else-if="activeTab === 'timeline'"
      class="rounded-xl border border-slate-200 bg-white p-6"
    >
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
        Communication timeline
      </h3>
      <ul v-if="store.timeline.length" class="mt-4 space-y-3">
        <li
          v-for="(item, index) in store.timeline"
          :key="`${item.source}-${item.uuid}-${index}`"
          class="border-l-2 border-slate-200 pl-3"
        >
          <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
          <p class="text-xs capitalize text-slate-500">
            {{ item.source }} · {{ item.type }} · {{ formatDate(item.occurred_at) }}
          </p>
          <p v-if="item.summary" class="mt-1 text-sm text-slate-600">{{ item.summary }}</p>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-500">No timeline entries yet.</p>
    </div>

    <!-- Notes -->
    <div v-else-if="activeTab === 'notes'" class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]">
      <form
        class="space-y-3 rounded-xl border border-slate-200 bg-white p-4"
        @submit.prevent="submitNote"
      >
        <h3 class="text-sm font-semibold text-slate-800">Add note</h3>
        <select v-model="noteForm.note_type" class="input" required>
          <option value="general">Note</option>
          <option value="internal">Internal comment</option>
          <option value="meeting">Meeting note</option>
        </select>
        <input v-model="noteForm.title" type="text" class="input" placeholder="Title (optional)" />
        <textarea
          v-model="noteForm.body"
          rows="4"
          class="input"
          required
          placeholder="Write a note..."
        />
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
          <input v-model="noteForm.is_pinned" type="checkbox" class="rounded border-slate-300" />
          Pin note
        </label>
        <button
          type="submit"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          :disabled="store.saving"
        >
          Save note
        </button>
      </form>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex gap-2">
          <button
            v-for="type in noteFilters"
            :key="type.value"
            type="button"
            class="rounded-md px-2 py-1 text-xs font-medium"
            :class="
              noteTypeFilter === type.value
                ? 'bg-brand-50 text-brand-700'
                : 'bg-slate-100 text-slate-600'
            "
            @click="filterNotes(type.value)"
          >
            {{ type.label }}
          </button>
        </div>
        <ul v-if="store.notes.length" class="divide-y divide-slate-100">
          <li v-for="note in store.notes" :key="note.uuid" class="py-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-medium text-slate-900">
                  {{ note.title || note.note_type_label || note.note_type }}
                  <span v-if="note.is_pinned" class="ml-2 text-xs text-amber-700">Pinned</span>
                </p>
                <p class="text-xs capitalize text-slate-500">
                  {{ note.note_type }} · {{ formatDate(note.occurred_at || note.created_at) }}
                </p>
                <p class="mt-1 whitespace-pre-wrap text-sm text-slate-700">{{ note.body }}</p>
              </div>
              <button
                type="button"
                class="text-xs font-medium text-rose-700"
                @click="archiveNote(note.uuid)"
              >
                Archive
              </button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">No notes yet.</p>
      </div>
    </div>

    <!-- Tasks -->
    <div v-else-if="activeTab === 'tasks'" class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]">
      <form
        class="space-y-3 rounded-xl border border-slate-200 bg-white p-4"
        @submit.prevent="submitTask"
      >
        <h3 class="text-sm font-semibold text-slate-800">Create task</h3>
        <input
          v-model="taskForm.title"
          type="text"
          class="input"
          required
          placeholder="Task title"
        />
        <textarea v-model="taskForm.description" rows="3" class="input" placeholder="Description" />
        <select v-model="taskForm.priority" class="input">
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Due at</label
        >
        <input v-model="taskForm.due_at" type="datetime-local" class="input" />
        <label class="block text-xs font-medium uppercase tracking-wide text-slate-500"
          >Remind at</label
        >
        <input v-model="taskForm.remind_at" type="datetime-local" class="input" />
        <button
          type="submit"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          :disabled="store.saving"
        >
          Save task
        </button>
      </form>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <ul v-if="store.tasks.length" class="divide-y divide-slate-100">
          <li
            v-for="task in store.tasks"
            :key="task.uuid"
            class="flex flex-wrap items-center justify-between gap-3 py-3"
          >
            <div>
              <p class="text-sm font-medium text-slate-900">{{ task.title }}</p>
              <p class="text-xs capitalize text-slate-500">
                {{ task.status?.replaceAll('_', '') }} · {{ task.priority }} · due
                {{ formatDate(task.due_at) }}
              </p>
            </div>
            <div class="flex gap-2">
              <button
                v-if="task.status !== 'completed'"
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-emerald-700 hover:bg-emerald-50"
                @click="completeTask(task.uuid)"
              >
                Complete
              </button>
              <button
                type="button"
                class="rounded-md px-2 py-1 text-xs font-medium text-rose-700 hover:bg-rose-50"
                @click="archiveTask(task.uuid)"
              >
                Archive
              </button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">No tasks yet.</p>
      </div>
    </div>

    <!-- Calendar -->
    <div
      v-else-if="activeTab === 'calendar'"
      class="rounded-xl border border-slate-200 bg-white p-6"
    >
      <div class="mb-4 flex flex-wrap items-end gap-3">
        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >From</label
          >
          <input v-model="calendarFrom" type="date" class="input" />
        </div>
        <div>
          <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500"
            >To</label
          >
          <input v-model="calendarTo" type="date" class="input" />
        </div>
        <button
          type="button"
          class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          @click="loadCalendar"
        >
          Refresh
        </button>
      </div>
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
        Reminder calendar
      </h3>
      <ul v-if="store.reminders.length" class="mt-4 divide-y divide-slate-100">
        <li v-for="item in store.reminders" :key="item.uuid" class="py-3">
          <p class="text-sm font-medium text-slate-900">{{ item.title }}</p>
          <p class="text-xs text-slate-500">
            Remind {{ formatDate(item.remind_at) }} · Due {{ formatDate(item.due_at) }} ·
            {{ item.priority }}
          </p>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-500">No reminders in this range.</p>
    </div>

    <!-- Email / communications -->
    <div v-else-if="activeTab === 'emails'" class="grid gap-4 lg:grid-cols-[22rem_minmax(0,1fr)]">
      <form
        class="space-y-3 rounded-xl border border-slate-200 bg-white p-4"
        @submit.prevent="submitCommunication"
      >
        <h3 class="text-sm font-semibold text-slate-800">Log communication</h3>
        <select v-model="commForm.type" class="input" required>
          <option value="email">Email</option>
          <option value="call">Call log</option>
          <option value="meeting">Meeting</option>
        </select>
        <select v-model="commForm.direction" class="input">
          <option value="outbound">Outbound</option>
          <option value="inbound">Inbound</option>
          <option value="internal">Internal</option>
        </select>
        <input v-model="commForm.subject" type="text" class="input" placeholder="Subject" />
        <textarea v-model="commForm.body" rows="4" class="input" placeholder="Summary / body" />
        <input
          v-if="commForm.type === 'call'"
          v-model="commForm.duration_seconds"
          type="number"
          min="0"
          class="input"
          placeholder="Duration (seconds)"
        />
        <input v-model="commForm.occurred_at" type="datetime-local" class="input" />
        <button
          type="submit"
          class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
          :disabled="store.saving"
        >
          Log entry
        </button>
      </form>
      <div class="rounded-xl border border-slate-200 bg-white p-4">
        <div class="mb-3 flex gap-2">
          <button
            v-for="type in commFilters"
            :key="type.value"
            type="button"
            class="rounded-md px-2 py-1 text-xs font-medium"
            :class="
              commTypeFilter === type.value
                ? 'bg-brand-50 text-brand-700'
                : 'bg-slate-100 text-slate-600'
            "
            @click="filterCommunications(type.value)"
          >
            {{ type.label }}
          </button>
        </div>
        <ul v-if="store.communications.length" class="divide-y divide-slate-100">
          <li v-for="item in store.communications" :key="item.uuid" class="py-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-sm font-medium text-slate-900">
                  {{ item.subject || item.type_label || item.type }}
                </p>
                <p class="text-xs capitalize text-slate-500">
                  {{ item.type }} · {{ item.direction }} · {{ formatDate(item.occurred_at) }}
                </p>
                <p v-if="item.body" class="mt-1 text-sm text-slate-700">{{ item.body }}</p>
              </div>
              <button
                type="button"
                class="text-xs font-medium text-rose-700"
                @click="archiveCommunication(item.uuid)"
              >
                Archive
              </button>
            </div>
          </li>
        </ul>
        <p v-else class="text-sm text-slate-500">No communications logged yet.</p>
      </div>
    </div>

    <!-- Activity -->
    <div
      v-else-if="activeTab === 'activity'"
      class="rounded-xl border border-slate-200 bg-white p-6"
    >
      <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
        Activity timeline
      </h3>
      <ul v-if="store.activity.length" class="mt-4 space-y-3">
        <li
          v-for="(item, index) in store.activity"
          :key="item.id || index"
          class="border-l-2 border-slate-200 pl-3"
        >
          <p class="text-sm font-medium text-slate-900">
            {{ item.description || item.event || 'Activity' }}
          </p>
          <p class="text-xs text-slate-500">
            {{ item.subject_type }} · {{ formatDate(item.created_at) }}
          </p>
        </li>
      </ul>
      <p v-else class="mt-3 text-sm text-slate-500">
        No activity yet. Create notes, tasks, or communications to populate this feed.
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import { useCustomersStore } from '@/modules/customers/stores/customers';
import { useCommunicationStore } from '@/modules/customers/stores/communication';

const route = useRoute();
const customersStore = useCustomersStore();
const store = useCommunicationStore();

const activeTab = ref('timeline');
const noteTypeFilter = ref('');
const commTypeFilter = ref('email');
const calendarFrom = ref('');
const calendarTo = ref('');

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

const noteForm = reactive({
  note_type: 'general',
  title: '',
  body: '',
  is_pinned: false,
});

const taskForm = reactive({
  title: '',
  description: '',
  priority: 'medium',
  due_at: '',
  remind_at: '',
});

const commForm = reactive({
  type: 'email',
  direction: 'outbound',
  subject: '',
  body: '',
  duration_seconds: '',
  occurred_at: '',
});

const customerName = computed(() => customersStore.currentCustomer?.display_name || 'customer');

const statCards = computed(() => {
  const stats = store.overview?.statistics || {};
  return [
    { label: 'Notes', value: stats.notes?.total ?? 0 },
    { label: 'Open tasks', value: (stats.tasks?.open ?? 0) + (stats.tasks?.in_progress ?? 0) },
    { label: 'Emails', value: stats.communications?.email ?? 0 },
    { label: 'Upcoming reminders', value: stats.tasks?.upcoming_reminders ?? 0 },
  ];
});

onMounted(async () => {
  await customersStore.fetchCustomer(route.params.id);
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 0);
  calendarFrom.value = toDateInput(start);
  calendarTo.value = toDateInput(end);
  await store.fetchOverview(route.params.id);
});

async function switchTab(tab) {
  activeTab.value = tab;
  if (tab === 'timeline') await store.fetchTimeline({ customer: route.params.id });
  if (tab === 'notes')
    await store.fetchNotes({
      customer: route.params.id,
      note_type: noteTypeFilter.value || undefined,
    });
  if (tab === 'tasks') await store.fetchTasks({ customer: route.params.id });
  if (tab === 'calendar') await loadCalendar();
  if (tab === 'emails')
    await store.fetchCommunications({
      customer: route.params.id,
      type: commTypeFilter.value || undefined,
    });
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

async function submitNote() {
  await store.createNote({
    customer_id: route.params.id,
    note_type: noteForm.note_type,
    title: noteForm.title || null,
    body: noteForm.body,
    is_pinned: noteForm.is_pinned,
  });
  noteForm.title = '';
  noteForm.body = '';
  noteForm.is_pinned = false;
  await store.fetchNotes({
    customer: route.params.id,
    note_type: noteTypeFilter.value || undefined,
  });
  await store.fetchOverview(route.params.id);
}

async function submitTask() {
  await store.createTask({
    customer_id: route.params.id,
    title: taskForm.title,
    description: taskForm.description || null,
    priority: taskForm.priority,
    due_at: taskForm.due_at ? new Date(taskForm.due_at).toISOString() : null,
    remind_at: taskForm.remind_at ? new Date(taskForm.remind_at).toISOString() : null,
  });
  taskForm.title = '';
  taskForm.description = '';
  taskForm.due_at = '';
  taskForm.remind_at = '';
  await store.fetchTasks({ customer: route.params.id });
  await store.fetchOverview(route.params.id);
}

async function submitCommunication() {
  await store.createCommunication({
    customer_id: route.params.id,
    type: commForm.type,
    direction: commForm.direction,
    subject: commForm.subject || null,
    body: commForm.body || null,
    duration_seconds: commForm.duration_seconds ? Number(commForm.duration_seconds) : null,
    occurred_at: commForm.occurred_at
      ? new Date(commForm.occurred_at).toISOString()
      : new Date().toISOString(),
  });
  commForm.subject = '';
  commForm.body = '';
  commForm.duration_seconds = '';
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

async function archiveNote(id) {
  await store.archiveNote(id);
  await store.fetchNotes({
    customer: route.params.id,
    note_type: noteTypeFilter.value || undefined,
  });
}

async function archiveTask(id) {
  await store.archiveTask(id);
  await store.fetchTasks({ customer: route.params.id });
}

async function archiveCommunication(id) {
  await store.archiveCommunication(id);
  await store.fetchCommunications({
    customer: route.params.id,
    type: commTypeFilter.value || undefined,
  });
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

function toDateInput(date) {
  const pad = (n) => String(n).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
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

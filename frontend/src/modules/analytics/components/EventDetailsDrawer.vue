<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex justify-end bg-slate-900/40"
    @click.self="$emit('close')"
  >
    <aside class="flex h-full w-full max-w-lg flex-col bg-white shadow-xl">
      <div class="flex items-start justify-between gap-3 border-b border-zinc-100 px-6 py-5">
        <div class="min-w-0">
          <h3 class="truncate text-lg font-semibold text-slate-900">{{ event?.event_name || 'Event details' }}</h3>
          <p class="mt-0.5 truncate text-sm text-slate-500">{{ formatLabel(event?.event_source) || 'Analytics event' }}</p>
        </div>
        <button
          type="button"
          class="shrink-0 rounded-[12px] border border-zinc-200 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-6">
        <div v-if="event?.category" class="mb-5">
          <span
            class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset"
            :class="categoryClasses"
          >
            {{ formatLabel(event.category) }}
          </span>
        </div>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Occurred</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatDate(event?.occurred_at) }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Source</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ formatLabel(event?.event_source) || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Actor</dt>
            <dd class="mt-1 text-sm text-slate-900">
              {{ event?.user?.full_name || event?.user?.email || 'System' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Company</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ event?.company?.company_name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Application</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ event?.application?.name || '—' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">IP address</dt>
            <dd class="mt-1 font-mono text-sm text-slate-900">{{ event?.ip_address || '—' }}</dd>
          </div>
          <div class="sm:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-slate-400">Subject</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ subjectLabel }}</dd>
          </div>
        </dl>

        <div v-if="hasProperties" class="mt-6">
          <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Properties</h4>
          <pre class="overflow-x-auto rounded-[12px] bg-slate-950 p-4 text-xs leading-relaxed text-slate-100">{{ pretty(event.properties) }}</pre>
        </div>

        <div v-if="hasMetrics" class="mt-6">
          <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Metrics</h4>
          <pre class="overflow-x-auto rounded-[12px] bg-slate-950 p-4 text-xs leading-relaxed text-slate-100">{{ pretty(event.metrics) }}</pre>
        </div>
      </div>
    </aside>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  event: { type: Object, default: null },
});

defineEmits(['close']);

const categoryStyles = {
  business: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  customer: 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
  application: 'bg-amber-50 text-amber-800 ring-amber-600/20',
  api: 'bg-violet-50 text-violet-700 ring-violet-600/20',
  operational: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  security: 'bg-rose-50 text-rose-700 ring-rose-600/20',
  system: 'bg-zinc-100 text-zinc-700 ring-zinc-500/15',
  executive: 'bg-brand-50 text-brand-700 ring-brand-200',
};

const categoryClasses = computed(
  () => categoryStyles[String(props.event?.category || '').toLowerCase()] || 'bg-zinc-100 text-zinc-700 ring-zinc-500/15',
);

const subjectLabel = computed(() => {
  const event = props.event;
  if (!event) {
    return '—';
  }
  if (event.subject_type) {
    const type = String(event.subject_type).split('\\').pop();
    return event.subject_id ? `${type} #${event.subject_id}` : type;
  }
  return '—';
});

const hasProperties = computed(() => hasEntries(props.event?.properties));
const hasMetrics = computed(() => hasEntries(props.event?.metrics));

function hasEntries(value) {
  return Boolean(value && typeof value === 'object' && Object.keys(value).length);
}

function pretty(value) {
  return JSON.stringify(value || {}, null, 2);
}

function formatLabel(value) {
  if (!value) {
    return '';
  }

  return String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (character) => character.toUpperCase());
}

function formatDate(value) {
  if (!value) {
    return '—';
  }
  return new Date(value).toLocaleString();
}
</script>

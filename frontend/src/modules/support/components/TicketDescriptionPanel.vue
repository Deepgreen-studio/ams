<template>
  <div class="space-y-4">
    <div
      class="rounded-[12px] p-4 ring-1"
      :class="
        isSms
          ? 'bg-sky-50/70 ring-sky-100'
          : parsed.isIngested
            ? 'bg-violet-50/50 ring-violet-100'
            : 'bg-zinc-50/80 ring-zinc-100'
      "
    >
      <div class="mb-3 flex flex-wrap items-center gap-2">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
          :class="
            isSms
              ? 'bg-sky-100 text-sky-800'
              : parsed.isIngested
                ? 'bg-violet-100 text-violet-800'
                : 'bg-zinc-200 text-slate-700'
          "
        >
          <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
          {{ channelLabel }}
        </span>
        <span v-if="parsed.isIngested" class="text-xs text-slate-500">
          Auto-ingested via webhook
        </span>
      </div>

      <p v-if="parsed.body" class="whitespace-pre-wrap text-sm leading-6 text-slate-900">
        {{ parsed.body }}
      </p>
      <p v-else class="text-sm italic text-slate-500">No message body.</p>

      <div
        v-if="parsed.contact.from || parsed.contact.to"
        class="mt-4 flex flex-wrap gap-x-4 gap-y-1 border-t border-white/80 pt-3 text-xs text-slate-600"
      >
        <span v-if="parsed.contact.from">
          <span class="font-medium text-slate-500">From</span>
          {{ parsed.contact.from }}
        </span>
        <span v-if="parsed.contact.to">
          <span class="font-medium text-slate-500">To</span>
          {{ parsed.contact.to }}
        </span>
      </div>
    </div>

    <div
      v-if="showContactCard"
      class="rounded-[12px] bg-white p-4 ring-1 ring-zinc-100"
    >
      <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Contact</h4>
      <dl class="mt-3 grid gap-3 sm:grid-cols-2">
        <div v-if="parsed.contact.name">
          <dt class="text-[11px] uppercase tracking-wide text-slate-400">Name</dt>
          <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ parsed.contact.name }}</dd>
        </div>
        <div v-if="parsed.contact.email">
          <dt class="text-[11px] uppercase tracking-wide text-slate-400">Email</dt>
          <dd class="mt-0.5 text-sm text-slate-900">
            <a :href="`mailto:${parsed.contact.email}`" class="text-brand-700 hover:underline">
              {{ parsed.contact.email }}
            </a>
          </dd>
        </div>
        <div v-if="parsed.contact.phone && parsed.contact.phone !== parsed.contact.from">
          <dt class="text-[11px] uppercase tracking-wide text-slate-400">Phone</dt>
          <dd class="mt-0.5 text-sm text-slate-900">{{ parsed.contact.phone }}</dd>
        </div>
      </dl>
    </div>

    <div
      v-if="parsed.isIngested && parsed.ingestMeta.length"
      class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
    >
      <button
        type="button"
        class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
        @click="metaOpen = !metaOpen"
      >
        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
          Ingest details
        </span>
        <span class="text-xs font-medium text-brand-700">{{ metaOpen ? 'Hide' : 'Show' }}</span>
      </button>
      <dl v-if="metaOpen" class="space-y-2.5 border-t border-zinc-100 px-4 py-3">
        <div
          v-for="item in parsed.ingestMeta"
          :key="item.key"
          class="grid gap-0.5 sm:grid-cols-[8rem_1fr] sm:gap-3"
        >
          <dt class="text-[11px] uppercase tracking-wide text-slate-400">{{ item.label }}</dt>
          <dd class="break-all font-mono text-xs text-slate-700">{{ item.value }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { parseTicketDescription } from '@/modules/support/utils/parseTicketDescription';

const props = defineProps({
  description: { type: String, default: '' },
  source: { type: String, default: '' },
  hasLinkedCustomer: { type: Boolean, default: false },
});

const metaOpen = ref(true);

const parsed = computed(() => parseTicketDescription(props.description));

const isSms = computed(() => props.source === 'sms');

const channelLabel = computed(() => {
  if (isSms.value) return 'SMS message';
  if (parsed.value.isIngested) return 'Inbound message';
  return 'Description';
});

const showContactCard = computed(() => {
  if (props.hasLinkedCustomer) return false;
  const c = parsed.value.contact;
  return Boolean(c.name || c.email || (c.phone && c.phone !== c.from));
});
</script>

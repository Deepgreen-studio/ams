<template>
  <div
    class="rounded-[12px] p-2.5"
    :class="inverted
      ? 'bg-white/15 text-white'
      : 'bg-white/80 ring-1 ring-zinc-100 text-slate-800'"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0">
        <p class="truncate text-xs font-medium" :class="inverted ? 'text-white' : 'text-slate-900'">
          {{ attachment.original_filename }}
        </p>
        <p class="text-[11px]" :class="inverted ? 'text-white/70' : 'text-slate-500'">
          {{ attachment.attachment_type_label || attachment.attachment_type }}
          · {{ formatSize(attachment.size) }}
        </p>
      </div>
      <div class="flex shrink-0 gap-1.5">
        <button
          v-if="attachment.is_previewable"
          type="button"
          class="rounded-lg px-2 py-1 text-[11px] font-medium"
          :class="inverted ? 'text-white hover:bg-white/10' : 'text-brand-700 hover:bg-brand-50'"
          @click="loadPreview"
        >
          Preview
        </button>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-[11px] font-medium"
          :class="inverted ? 'text-white/90 hover:bg-white/10' : 'text-slate-700 hover:bg-zinc-50'"
          @click="download"
        >
          Download
        </button>
      </div>
    </div>
    <div v-if="previewUrl && attachment.is_image" class="mt-2 overflow-hidden rounded-[10px] bg-black/5">
      <img :src="previewUrl" :alt="attachment.original_filename" class="max-h-56 w-full object-contain" />
    </div>
    <div v-else-if="previewUrl && attachment.is_video" class="mt-2 overflow-hidden rounded-[10px] bg-black/5">
      <video :src="previewUrl" controls class="max-h-56 w-full" />
    </div>
    <p v-if="error" class="mt-2 text-[11px]" :class="inverted ? 'text-rose-100' : 'text-rose-600'">{{ error }}</p>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { supportTicketService } from '@/modules/support/services/supportTicketService';

const props = defineProps({
  ticketId: { type: String, required: true },
  attachment: { type: Object, required: true },
  inverted: { type: Boolean, default: false },
});

const previewUrl = ref('');
const error = ref('');

onBeforeUnmount(() => {
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
});

function formatSize(bytes) {
  const value = Number(bytes || 0);
  if (value < 1024) return `${value} B`;
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
  return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

async function loadPreview() {
  error.value = '';
  try {
    const { data } = await supportTicketService.previewAttachment(props.ticketId, props.attachment.uuid);
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(data);
  } catch (err) {
    error.value = err?.message || 'Unable to preview attachment';
  }
}

async function download() {
  error.value = '';
  try {
    const { data } = await supportTicketService.downloadAttachment(props.ticketId, props.attachment.uuid);
    const url = URL.createObjectURL(data);
    const link = document.createElement('a');
    link.href = url;
    link.download = props.attachment.original_filename || 'attachment';
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  } catch (err) {
    error.value = err?.message || 'Unable to download attachment';
  }
}
</script>

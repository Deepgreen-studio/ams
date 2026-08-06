<template>
  <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
    <div class="mb-2 flex items-center justify-between gap-2">
      <div>
        <p class="text-sm font-medium text-slate-900">{{ attachment.original_filename }}</p>
        <p class="text-xs text-slate-500">
          {{ attachment.attachment_type_label || attachment.attachment_type }}
          · {{ formatSize(attachment.size) }}
        </p>
      </div>
      <div class="flex gap-2">
        <button
          v-if="attachment.is_previewable"
          type="button"
          class="rounded-md px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-50"
          @click="loadPreview"
        >
          Preview
        </button>
        <button
          type="button"
          class="rounded-md px-2 py-1 text-xs font-medium text-slate-700 hover:bg-white"
          @click="download"
        >
          Download
        </button>
      </div>
    </div>
    <div v-if="previewUrl && attachment.is_image" class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <img :src="previewUrl" :alt="attachment.original_filename" class="max-h-72 w-full object-contain" />
    </div>
    <div v-else-if="previewUrl && attachment.is_video" class="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <video :src="previewUrl" controls class="max-h-72 w-full" />
    </div>
    <p v-if="error" class="mt-2 text-xs text-rose-600">{{ error }}</p>
  </div>
</template>

<script setup>
import { onBeforeUnmount, ref } from 'vue';
import { supportTicketService } from '@/modules/support/services/supportTicketService';

const props = defineProps({
  ticketId: { type: String, required: true },
  attachment: { type: Object, required: true },
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

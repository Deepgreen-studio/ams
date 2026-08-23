<template>
  <div class="mt-4">
    <div class="mb-1.5 flex items-center justify-between gap-2">
      <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ label }}</p>
      <button
        type="button"
        class="rounded-lg px-2 py-1 text-xs font-medium text-slate-500 hover:bg-zinc-100 hover:text-slate-800"
        @click="copy"
      >
        {{ copied ? 'Copied' : 'Copy' }}
      </button>
    </div>
    <pre
      class="overflow-x-auto rounded-[12px] bg-slate-900 p-4 text-xs leading-6 text-slate-100"
    ><code>{{ code }}</code></pre>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  code: { type: String, required: true },
  label: { type: String, default: 'Example' },
});

const copied = ref(false);

async function copy() {
  try {
    await navigator.clipboard.writeText(props.code);
    copied.value = true;
    window.setTimeout(() => {
      copied.value = false;
    }, 1600);
  } catch {
    copied.value = false;
  }
}
</script>

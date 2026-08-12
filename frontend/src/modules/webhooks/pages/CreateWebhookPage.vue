<template>
  <div>
    <!-- <PageHeader
      title="Create webhook"
      description="Register an incoming or outgoing webhook endpoint."
    /> -->
    <WebhookSubnav />
    <div class="overflow-hidden rounded-[12px] bg-white p-6 sm:p-8 ring-1 ring-zinc-100">
      <WebhookForm
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Create webhook"
        @submit="onSubmit"
        @cancel="router.push({ name: 'webhooks.index' })"
      />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import WebhookForm from '@/modules/webhooks/components/WebhookForm.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const router = useRouter();
const store = useWebhooksStore();

async function onSubmit(payload) {
  const webhook = await store.createWebhook(payload);
  await router.push({ name: 'webhooks.show', params: { id: webhook.uuid } });
}
</script>

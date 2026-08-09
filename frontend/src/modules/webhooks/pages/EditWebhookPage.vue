<template>
  <div>
    <!-- <PageHeader
      title="Edit webhook"
      description="Update webhook configuration and subscriptions."
    /> -->
    <WebhookSubnav />
    <div
      v-if="store.loading && !store.currentWebhook"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />
    <div v-else class="rounded-xl border border-slate-200 bg-white p-6">
      <WebhookForm
        :initial="store.currentWebhook || {}"
        hide-company
        :loading="store.saving"
        :errors="store.fieldErrors"
        :error="store.error || ''"
        submit-label="Save changes"
        @submit="onSubmit"
        @cancel="router.push({ name: 'webhooks.show', params: { id: route.params.id } })"
      />
    </div>
  </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import WebhookForm from '@/modules/webhooks/components/WebhookForm.vue';
import WebhookSubnav from '@/modules/webhooks/components/WebhookSubnav.vue';
import { useWebhooksStore } from '@/modules/webhooks/stores/webhooks';

const route = useRoute();
const router = useRouter();
const store = useWebhooksStore();

onMounted(() => store.fetchWebhook(route.params.id));

async function onSubmit(payload) {
  const { company_id, ...updatePayload } = payload;
  await store.updateWebhook(route.params.id, updatePayload);
  await router.push({ name: 'webhooks.show', params: { id: route.params.id } });
}
</script>

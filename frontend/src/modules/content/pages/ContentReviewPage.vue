<template>
  <div>
    <PageHeader
      :title="content?.title || 'Content review'"
      description="Review screen for the linear approval workflow."
    >
      <template #actions>
        <RouterLink
          :to="{ name: 'content.workflow' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to queue
        </RouterLink>
      </template>
    </PageHeader>

    <ContentItemSubnav v-if="content" :content-id="content.uuid" />

    <div
      v-if="contentStore.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ contentStore.successMessage }}
    </div>
    <div
      v-if="contentStore.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ contentStore.error }}
    </div>

    <div
      v-if="contentStore.loading && !content"
      class="h-64 animate-pulse rounded-xl bg-slate-100"
    />

    <div v-else-if="content" class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="rounded-xl border border-slate-200 bg-white p-6">
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <StatusBadge :status="content.status?.slug" :label="content.status?.name" />
            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{
              content.type?.name
            }}</span>
            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs text-slate-600"
              >v{{ content.version || 1 }}</span
            >
          </div>
          <h2 class="text-xl font-semibold text-slate-900">{{ content.title }}</h2>
          <p class="mt-2 text-sm text-slate-600">
            {{ content.summary || content.excerpt || 'No summary.' }}
          </p>
          <div class="prose mt-6 max-w-none whitespace-pre-wrap text-sm text-slate-700">
            {{ content.body || '—' }}
          </div>
        </div>

        <WorkflowTimeline :history="contentStore.workflowHistory" />
      </section>

      <aside class="space-y-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <p class="text-xs uppercase tracking-wide text-slate-500">Current stage</p>
          <p class="mt-1 text-base font-semibold text-slate-900">{{ content.status?.name }}</p>
          <p class="mt-1 text-sm text-slate-500">Next level: {{ nextLevelLabel }}</p>

          <label class="mb-1 mt-4 block text-sm font-medium text-slate-700">Comments</label>
          <textarea
            v-model="comments"
            rows="4"
            class="w-full h-12 rounded-[12px] border border-slate-300 px-3 text-sm"
            placeholder="Reviewer comments…"
          />

          <div class="mt-4 flex flex-col gap-2">
            <button
              v-if="status === 'draft' || status === 'rejected'"
              type="button"
              class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('submit')"
            >
              Submit for review
            </button>
            <button
              v-if="status === 'pending_review'"
              type="button"
              class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('review')"
            >
              Mark reviewed
            </button>
            <button
              v-if="status === 'reviewed'"
              type="button"
              class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('approve')"
            >
              Approve
            </button>
            <button
              v-if="status === 'approved'"
              type="button"
              class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('publish')"
            >
              Publish
            </button>
            <button
              v-if="['pending_review', 'reviewed', 'approved'].includes(status)"
              type="button"
              class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('reject')"
            >
              Reject
            </button>
            <button
              v-if="status === 'rejected' || status === 'pending_review'"
              type="button"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('returnToDraft')"
            >
              Return to draft
            </button>
            <button
              v-if="status === 'published'"
              type="button"
              class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('archive')"
            >
              Archive
            </button>
          </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 text-sm text-slate-600">
          <p>
            <span class="font-medium text-slate-800">Submitted:</span>
            {{ formatDate(content.submitted_at) }}
          </p>
          <p class="mt-2">
            <span class="font-medium text-slate-800">Reviewed:</span>
            {{ formatDate(content.reviewed_at) }}
          </p>
          <p class="mt-2">
            <span class="font-medium text-slate-800">Approved:</span>
            {{ formatDate(content.approved_at) }}
          </p>
          <p class="mt-2">
            <span class="font-medium text-slate-800">Published:</span>
            {{ formatDate(content.published_at) }}
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import PageHeader from '@/components/ui/PageHeader.vue';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import WorkflowTimeline from '@/modules/content/components/WorkflowTimeline.vue';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const contentStore = useContentStore();
const comments = ref('');
const content = computed(() => contentStore.currentContent);
const status = computed(() => content.value?.status?.slug || '');

const nextLevelLabel = computed(() => {
  switch (status.value) {
    case 'draft':
    case 'rejected':
      return 'Editor';
    case 'pending_review':
      return 'Editor → Manager';
    case 'reviewed':
      return 'Manager';
    case 'approved':
      return 'Administrator';
    default:
      return '—';
  }
});

onMounted(async () => {
  await contentStore.fetchContent(route.params.id);
  await contentStore.fetchWorkflowHistory(route.params.id);
});

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}

async function act(action) {
  const payload = { comments: comments.value || undefined };
  if (action === 'reject' && !comments.value.trim()) {
    contentStore.error = 'Rejection comments are required.';
    return;
  }
  await contentStore.runWorkflow(action, route.params.id, payload);
  comments.value = '';
}
</script>

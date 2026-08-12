<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'content.workflow' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Back to queue
      </RouterLink>
    </Teleport>

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
      class="h-64 animate-pulse rounded-[12px] bg-zinc-100"
    />

    <div v-else-if="content" class="grid gap-4 lg:grid-cols-3">
      <section class="space-y-4 lg:col-span-2">
        <div class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100 sm:p-8">
          <div class="mb-4 flex flex-wrap items-center gap-2">
            <StatusBadge :status="content.status?.slug" :label="content.status?.name" />
            <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
              {{ content.type?.name }}
            </span>
            <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
              v{{ content.version || 1 }}
            </span>
          </div>
          <h2 class="text-xl font-semibold tracking-tight text-slate-900">{{ content.title }}</h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">
            {{ content.summary || content.excerpt || 'No summary.' }}
          </p>

          <div class="mt-6 border-t border-zinc-100 pt-6">
            <div
              v-if="!content.body"
              class="rounded-[12px] border border-dashed border-zinc-200 bg-zinc-50 px-4 py-10 text-center text-sm text-slate-500"
            >
              No body content yet.
            </div>
            <ContentPreview
              v-else
              body-only
              :body="content.body"
              :body-format="content.body_format || 'rich'"
            />
          </div>
        </div>

        <WorkflowTimeline :history="contentStore.workflowHistory" />
      </section>

      <aside class="space-y-4">
        <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-6">
          <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Current stage</p>
          <p class="mt-1 text-base font-semibold text-slate-900">{{ content.status?.name }}</p>
          <p class="mt-1 text-sm text-slate-500">Next level: {{ nextLevelLabel }}</p>

          <label class="mb-1.5 mt-5 block text-sm font-medium text-slate-700">Comments</label>
          <textarea
            v-model="comments"
            rows="4"
            class="w-full rounded-[12px] border border-zinc-200 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-zinc-400 focus:border-brand-500"
            placeholder="Reviewer comments…"
          />

          <div class="mt-4 flex flex-col gap-2">
            <button
              v-if="(status === 'draft' || status === 'rejected') && can('content.submit')"
              type="button"
              class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('submit')"
            >
              Submit for review
            </button>
            <button
              v-if="status === 'pending_review' && can('content.review')"
              type="button"
              class="rounded-[12px] bg-violet-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-violet-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('review')"
            >
              Mark reviewed
            </button>
            <button
              v-if="status === 'reviewed' && can('content.approve')"
              type="button"
              class="rounded-[12px] bg-teal-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('approve')"
            >
              Approve
            </button>
            <button
              v-if="status === 'approved' && can('content.publish')"
              type="button"
              class="rounded-[12px] bg-emerald-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('publish')"
            >
              Publish
            </button>
            <button
              v-if="['pending_review', 'reviewed', 'approved'].includes(status) && canAny('content.review', 'content.approve', 'content.publish')"
              type="button"
              class="rounded-[12px] bg-rose-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-rose-700 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('reject')"
            >
              Reject
            </button>
            <button
              v-if="(status === 'rejected' || status === 'pending_review') && canAny('content.submit', 'content.update')"
              type="button"
              class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('returnToDraft')"
            >
              Return to draft
            </button>
            <button
              v-if="status === 'published' && canAny('content.publish', 'content.update')"
              type="button"
              class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50 disabled:opacity-60"
              :disabled="contentStore.saving"
              @click="act('archive')"
            >
              Archive
            </button>
          </div>
        </div>

        <div class="rounded-[12px] bg-white p-5 text-sm text-slate-600 ring-1 ring-zinc-100 sm:p-6">
          <dl class="space-y-3">
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Submitted</dt>
              <dd class="font-medium text-slate-800">{{ formatDate(content.submitted_at) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Reviewed</dt>
              <dd class="font-medium text-slate-800">{{ formatDate(content.reviewed_at) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Approved</dt>
              <dd class="font-medium text-slate-800">{{ formatDate(content.approved_at) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3">
              <dt class="text-slate-500">Published</dt>
              <dd class="font-medium text-slate-800">{{ formatDate(content.published_at) }}</dd>
            </div>
          </dl>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import ContentItemSubnav from '@/modules/content/components/ContentItemSubnav.vue';
import ContentPreview from '@/modules/content/components/ContentPreview.vue';
import StatusBadge from '@/modules/content/components/StatusBadge.vue';
import WorkflowTimeline from '@/modules/content/components/WorkflowTimeline.vue';
import { usePermissions } from '@/composables/usePermissions';
import { useContentStore } from '@/modules/content/stores/content';

const route = useRoute();
const contentStore = useContentStore();
const { can, canAny } = usePermissions();
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

<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        v-if="policy"
        :to="{ name: 'compliance.policies.versions', params: { id: policy.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ClockIcon class="h-4 w-4" />
        Version timeline
      </RouterLink>
      <RouterLink
        v-if="policy"
        :to="{ name: 'compliance.policies.compare', params: { id: policy.uuid } }"
        class="inline-flex items-center gap-2 rounded-[12px] border border-zinc-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        <ArrowsRightLeftIcon class="h-4 w-4" />
        Compare versions
      </RouterLink>
    </Teleport>

    <ComplianceSubnav />

    <div v-if="store.loading && !policy" class="grid gap-4 lg:grid-cols-3">
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100 lg:col-span-2" />
      <div class="h-80 animate-pulse rounded-[12px] bg-zinc-100" />
    </div>

    <div
      v-else-if="!policy"
      class="rounded-[12px] bg-white px-6 py-16 text-center ring-1 ring-zinc-100"
    >
      <p class="text-sm font-medium text-slate-900">Unable to load this policy</p>
      <p class="mt-1 text-xs text-slate-500">It may have been removed, or the request failed.</p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
        <button
          type="button"
          class="rounded-[12px] bg-brand-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-700"
          @click="reload"
        >
          Retry
        </button>
        <RouterLink
          :to="{ name: 'compliance.policies.index' }"
          class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
        >
          Back to policies
        </RouterLink>
      </div>
    </div>

    <div v-else class="space-y-4">
      <section class="rounded-[12px] bg-white px-6 py-5 ring-1 ring-zinc-100">
        <div class="flex flex-wrap items-center gap-2">
          <PolicyStatusBadge :status="policy.status" :label="policy.status_label" />
          <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            {{ policy.policy_type_label || policy.policy_type }}
          </span>
          <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-slate-600">
            v{{ policy.current_version ?? '—' }}
          </span>
        </div>
        <h1 class="mt-3 text-lg font-semibold text-slate-900">{{ policy.title }}</h1>
        <p class="mt-1 text-xs text-slate-500">
          {{ policy.policy_number }}
          <span v-if="policy.company?.company_name"> · {{ policy.company.company_name }}</span>
        </p>
      </section>

      <div class="grid items-start gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Document</h2>
            <p class="mt-2 whitespace-pre-wrap text-sm text-slate-600">
              {{ policy.description || 'No description provided.' }}
            </p>
            <div class="mt-5 whitespace-pre-wrap border-t border-zinc-100 pt-5 text-sm text-slate-800">
              {{ policy.body || 'Empty body' }}
            </div>
          </section>

          <section
            v-if="can('compliance.update')"
            class="overflow-hidden rounded-[12px] bg-white ring-1 ring-zinc-100"
          >
            <div class="border-b border-zinc-100 px-6 py-5">
              <h2 class="text-base font-semibold text-slate-900">Edit (creates new version)</h2>
              <p class="mt-0.5 text-xs text-slate-500">
                Saving appends an immutable snapshot. It does not overwrite previous versions.
              </p>
            </div>
            <div class="px-6 py-6">
              <PolicyForm
                :initial="policy"
                :loading="store.saving"
                :field-errors="store.fieldErrors"
                @submit="onUpdate"
                @cancel="() => {}"
              />
            </div>
          </section>
        </div>

        <div class="space-y-4">
          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Workflow</h2>
            <p class="mt-0.5 text-xs text-slate-500">Submit, approve, and publish this document</p>

            <button
              v-if="policy.status === 'draft' && can('compliance.update')"
              type="button"
              class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-[12px] bg-brand-600 px-5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
              :disabled="store.saving"
              @click="onSubmitForReview"
            >
              Submit for review
            </button>
            <button
              v-if="policy.status === 'approved' && can('compliance.update')"
              type="button"
              class="mt-4 inline-flex h-11 w-full items-center justify-center rounded-[12px] bg-sky-600 px-5 text-sm font-medium text-white hover:bg-sky-700 disabled:opacity-60"
              :disabled="store.saving"
              @click="onPublish"
            >
              Publish
            </button>
            <p v-if="policy.status === 'review'" class="mt-4 text-sm text-slate-600">
              Awaiting approval. Reviewers use the
              <RouterLink
                :to="{ name: 'compliance.policies.approvals' }"
                class="font-medium text-brand-700 hover:underline"
              >
                approval queue
              </RouterLink>.
            </p>

            <dl class="mt-5 space-y-3 border-t border-zinc-100 pt-5">
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Company</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ policy.company?.company_name || '—' }}</dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Review due</dt>
                <dd
                  class="mt-1 text-sm"
                  :class="isReviewOverdue ? 'font-medium text-rose-600' : 'text-slate-900'"
                >
                  {{ policy.review_due_at || '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Published at</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ policy.published_at || '—' }}</dd>
              </div>
            </dl>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">CMS version history</h2>
            <p class="mt-0.5 text-xs text-slate-500">Link this policy to a content item</p>

            <template v-if="store.cmsLink.linked && store.cmsLink.content">
              <p class="mt-4 text-sm text-slate-600">
                Linked to
                <RouterLink
                  :to="{ name: 'content.versions', params: { id: store.cmsLink.content.uuid } }"
                  class="font-medium text-brand-700 hover:underline"
                >
                  {{ store.cmsLink.content.title }}
                </RouterLink>
                (CMS v{{ store.cmsLink.content.version }})
              </p>
              <ul class="mt-3 divide-y divide-zinc-100 text-sm">
                <li
                  v-for="item in store.cmsLink.versions.slice(0, 5)"
                  :key="item.uuid"
                  class="flex justify-between py-2.5 first:pt-0"
                >
                  <span class="text-slate-900">CMS v{{ item.version }}</span>
                  <span class="text-slate-500">{{ item.status }}</span>
                </li>
              </ul>
              <RouterLink
                :to="{ name: 'content.versions', params: { id: store.cmsLink.content.uuid } }"
                class="mt-2 inline-block text-xs font-medium text-brand-700 hover:underline"
              >
                Open CMS timeline
              </RouterLink>
            </template>
            <template v-else>
              <p class="mt-4 text-sm text-slate-600">
                Link this policy to a CMS content item to share version history.
              </p>
              <div v-if="can('compliance.update')" class="mt-3 flex gap-2">
                <input
                  v-model="cmsContentId"
                  type="text"
                  class="input flex-1"
                  placeholder="CMS content UUID"
                  :disabled="store.saving"
                />
                <button
                  type="button"
                  class="inline-flex h-11 items-center rounded-[12px] bg-brand-600 px-4 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60"
                  :disabled="store.saving || !cmsContentId.trim()"
                  @click="onLinkCms"
                >
                  Link
                </button>
              </div>
            </template>
          </section>

          <section class="rounded-[12px] bg-white p-6 ring-1 ring-zinc-100">
            <h2 class="text-base font-semibold text-slate-900">Recent approvals</h2>
            <p class="mt-0.5 text-xs text-slate-500">Decisions recorded against this document</p>
            <div v-if="!(policy.approvals || []).length" class="py-8 text-center">
              <p class="text-sm font-medium text-slate-900">No approvals yet</p>
              <p class="mt-1 text-xs text-slate-500">Submit for review to open the approval workflow.</p>
            </div>
            <ul v-else class="mt-4 divide-y divide-zinc-100">
              <li
                v-for="item in policy.approvals"
                :key="item.uuid"
                class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0"
              >
                <div class="min-w-0">
                  <p class="text-sm font-medium text-slate-900">{{ item.status_label }}</p>
                  <p class="mt-0.5 text-xs text-slate-500">{{ item.comments || 'No comments' }}</p>
                </div>
                <PolicyStatusBadge :status="item.status" :label="item.status_label" />
              </li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import { ArrowsRightLeftIcon, ClockIcon } from '@heroicons/vue/24/outline';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyForm from '@/modules/compliance/components/PolicyForm.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const route = useRoute();
const store = usePolicyStore();
const toast = useToast();
const { can } = usePermissions();
const cmsContentId = ref('');

const policy = computed(() => store.current);

const isReviewOverdue = computed(() => {
  const due = policy.value?.review_due_at;
  if (!due) return false;
  return new Date(due) < new Date();
});

watch(
  () => store.error,
  (message) => {
    if (!message) return;
    toast.error(message);
    store.error = null;
  },
);

watch(
  () => store.successMessage,
  (message) => {
    if (!message) return;
    toast.success(message);
    store.successMessage = null;
  },
);

async function reload() {
  try {
    await store.fetchPolicy(route.params.id);
    await store.fetchCmsVersions(route.params.id).catch(() => {});
  } catch {
    // Toast is shown from store.error.
  }
}

onMounted(() => {
  store.successMessage = null;
  store.error = null;
  reload();
});

async function onUpdate(payload) {
  try {
    await store.updatePolicy(route.params.id, payload);
    toast.success(store.successMessage || 'Policy updated as a new version.');
    store.successMessage = null;
  } catch {
    // Toast is shown from store.error.
  }
}

async function onSubmitForReview() {
  try {
    await store.submitPolicy(route.params.id, { comments: 'Submitted from policy details' });
    toast.success(store.successMessage || 'Policy submitted for review.');
    store.successMessage = null;
  } catch {
    // Toast is shown from store.error.
  }
}

async function onPublish() {
  try {
    await store.publishPolicy(route.params.id);
    toast.success(store.successMessage || 'Policy published successfully.');
    store.successMessage = null;
  } catch {
    // Toast is shown from store.error.
  }
}

async function onLinkCms() {
  try {
    await store.linkCms(route.params.id, cmsContentId.value.trim());
    toast.success(store.successMessage || 'CMS content linked successfully.');
    store.successMessage = null;
    cmsContentId.value = '';
  } catch {
    // Toast is shown from store.error.
  }
}
</script>

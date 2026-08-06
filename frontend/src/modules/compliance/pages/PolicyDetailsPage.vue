<template>
  <div>
    <PageHeader
      :title="store.current?.title || 'Policy details'"
      :description="store.current?.policy_number || 'Policy governance and approval workflow'"
    >
      <template #actions>
        <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.policies.versions', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Version timeline
        </RouterLink>
        <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.policies.compare', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Compare versions
        </RouterLink>
      </template>
    </PageHeader>

    <ComplianceSubnav />

    <div
      v-if="store.successMessage"
      class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
    >
      {{ store.successMessage }}
    </div>
    <div
      v-if="store.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ store.error }}
    </div>

    <template v-if="store.current">
      <div class="mb-4 flex flex-wrap gap-2">
        <PolicyStatusBadge :status="store.current.status" :label="store.current.status_label" />
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
          {{ store.current.policy_type_label }}
        </span>
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
          v{{ store.current.current_version }}
        </span>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Document</h2>
            <p class="mb-4 text-sm text-slate-600">{{ store.current.description || 'No description' }}</p>
            <div class="prose prose-sm max-w-none whitespace-pre-wrap text-slate-800">
              {{ store.current.body || 'Empty body' }}
            </div>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Edit (creates new version)</h2>
            <PolicyForm
              :initial="store.current"
              :loading="store.saving"
              :error="store.error || ''"
              @submit="onUpdate"
              @cancel="() => {}"
            />
          </div>
        </div>

        <div class="space-y-4">
          <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
            <h2 class="text-sm font-semibold text-slate-900">Workflow</h2>
            <button
              v-if="store.current.status === 'draft'"
              type="button"
              class="w-full rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
              :disabled="store.saving"
              @click="onSubmit"
            >
              Submit for review
            </button>
            <button
              v-if="store.current.status === 'approved'"
              type="button"
              class="w-full rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white disabled:opacity-60"
              :disabled="store.saving"
              @click="onPublish"
            >
              Publish
            </button>
            <p v-if="store.current.status === 'review'" class="text-sm text-slate-600">
              Awaiting approval. Reviewers use the
              <RouterLink
                :to="{ name: 'compliance.policies.approvals' }"
                class="font-medium text-brand-700 hover:underline"
              >
                approval queue
              </RouterLink>.
            </p>
            <dl class="space-y-2 text-sm">
              <div>
                <dt class="text-slate-500">Company</dt>
                <dd class="font-medium text-slate-900">
                  {{ store.current.company?.company_name || '—' }}
                </dd>
              </div>
              <div>
                <dt class="text-slate-500">Review due</dt>
                <dd class="font-medium text-slate-900">{{ store.current.review_due_at || '—' }}</dd>
              </div>
              <div>
                <dt class="text-slate-500">Published at</dt>
                <dd class="font-medium text-slate-900">{{ store.current.published_at || '—' }}</dd>
              </div>
            </dl>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-3">
            <h2 class="text-sm font-semibold text-slate-900">CMS Version History</h2>
            <template v-if="store.cmsLink.linked && store.cmsLink.content">
              <p class="text-sm text-slate-600">
                Linked to
                <RouterLink
                  :to="{ name: 'content.versions', params: { id: store.cmsLink.content.uuid } }"
                  class="font-medium text-brand-700 hover:underline"
                >
                  {{ store.cmsLink.content.title }}
                </RouterLink>
                (CMS v{{ store.cmsLink.content.version }})
              </p>
              <ul class="divide-y divide-slate-100 text-sm">
                <li
                  v-for="item in store.cmsLink.versions.slice(0, 5)"
                  :key="item.uuid"
                  class="flex justify-between py-2"
                >
                  <span>CMS v{{ item.version }}</span>
                  <span class="text-slate-500">{{ item.status }}</span>
                </li>
              </ul>
              <RouterLink
                :to="{ name: 'content.versions', params: { id: store.cmsLink.content.uuid } }"
                class="text-xs font-medium text-brand-700 hover:underline"
              >
                Open CMS timeline
              </RouterLink>
            </template>
            <template v-else>
              <p class="text-sm text-slate-600">
                Link this policy to a CMS content item to share version history.
              </p>
              <div class="flex gap-2">
                <input
                  v-model="cmsContentId"
                  type="text"
                  class="input flex-1"
                  placeholder="CMS content UUID"
                />
                <button
                  type="button"
                  class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-60"
                  :disabled="store.saving || !cmsContentId"
                  @click="onLinkCms"
                >
                  Link
                </button>
              </div>
            </template>
          </div>

          <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-900">Recent approvals</h2>
            <EmptyState
              v-if="!(store.current.approvals || []).length"
              title="No approvals yet"
              description="Submit for review to open the approval workflow."
            />
            <ul v-else class="divide-y divide-slate-100 text-sm">
              <li
                v-for="item in store.current.approvals"
                :key="item.uuid"
                class="flex items-center justify-between py-2"
              >
                <div>
                  <p class="font-medium text-slate-900">{{ item.status_label }}</p>
                  <p class="text-xs text-slate-500">{{ item.comments || 'No comments' }}</p>
                </div>
                <PolicyStatusBadge :status="item.status" :label="item.status_label" />
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRoute } from 'vue-router';
import EmptyState from '@/components/ui/EmptyState.vue';
import PageHeader from '@/components/ui/PageHeader.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import PolicyForm from '@/modules/compliance/components/PolicyForm.vue';
import PolicyStatusBadge from '@/modules/compliance/components/PolicyStatusBadge.vue';
import { usePolicyStore } from '@/modules/compliance/stores/policies';

const route = useRoute();
const store = usePolicyStore();
const cmsContentId = ref('');

onMounted(async () => {
  await store.fetchPolicy(route.params.id);
  await store.fetchCmsVersions(route.params.id);
});

async function onUpdate(payload) {
  await store.updatePolicy(route.params.id, payload);
}

async function onSubmit() {
  await store.submitPolicy(route.params.id, { comments: 'Submitted from policy details' });
}

async function onPublish() {
  await store.publishPolicy(route.params.id);
}

async function onLinkCms() {
  await store.linkCms(route.params.id, cmsContentId.value.trim());
  cmsContentId.value = '';
}
</script>

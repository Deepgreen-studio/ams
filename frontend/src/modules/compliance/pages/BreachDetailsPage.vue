<template>
  <div>
    <!-- <PageHeader
      :title="store.current?.title || 'Breach details'"
      :description="store.current?.breach_number || 'Incident workflow, timeline, and notifications'"
    >
      <template #actions>
        <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.breaches.affected', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Affected users
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.breaches.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
      </template>
    </PageHeader> -->
    <Teleport defer to="#page-header-actions">
      <RouterLink
          v-if="store.current"
          :to="{ name: 'compliance.breaches.affected', params: { id: store.current.uuid } }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Affected users
        </RouterLink>
        <RouterLink
          :to="{ name: 'compliance.breaches.index' }"
          class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back
        </RouterLink>
    </Teleport>

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

    <div v-if="store.loading && !store.current" class="space-y-3">
      <div v-for="n in 4" :key="n" class="h-20 animate-pulse rounded-xl bg-slate-100" />
    </div>

    <template v-else-if="store.current">
      <div class="mb-4 flex flex-wrap gap-2">
        <BreachStatusBadge :status="store.current.status" :label="store.current.status_label" />
        <BreachSeverityBadge :severity="store.current.severity" :label="store.current.severity_label" />
        <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
          {{ store.current.breach_type_label || store.current.breach_type }}
        </span>
      </div>

      <div class="mb-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 lg:col-span-2">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Incident summary</h2>
          <dl class="grid gap-3 sm:grid-cols-2 text-sm">
            <div>
              <dt class="text-slate-500">Company</dt>
              <dd class="font-medium text-slate-900">{{ store.current.company?.company_name || '—' }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Affected users</dt>
              <dd class="font-medium text-slate-900">{{ store.current.affected_user_count }}</dd>
            </div>
            <div>
              <dt class="text-slate-500">Risk score</dt>
              <dd class="font-medium text-slate-900">
                {{ store.current.risk_score ?? '—' }}
                <span v-if="store.current.risk_level">({{ store.current.risk_level }})</span>
              </dd>
            </div>
            <div>
              <dt class="text-slate-500">Regulator deadline</dt>
              <dd class="font-medium text-slate-900">{{ formatDate(store.current.regulator_deadline_at) }}</dd>
            </div>
          </dl>
          <p class="mt-4 text-sm text-slate-600">{{ store.current.description || 'No description' }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Notification status</h2>
          <ul class="space-y-2 text-sm">
            <li class="flex justify-between gap-2">
              <span class="text-slate-500">Regulator required</span>
              <span>{{ store.current.regulator_notification_required ? 'Yes' : 'No' }}</span>
            </li>
            <li class="flex justify-between gap-2">
              <span class="text-slate-500">Regulator notified</span>
              <span>{{ formatDate(store.current.regulator_notified_at) }}</span>
            </li>
            <li class="flex justify-between gap-2">
              <span class="text-slate-500">Customer required</span>
              <span>{{ store.current.customer_notification_required ? 'Yes' : 'No' }}</span>
            </li>
            <li class="flex justify-between gap-2">
              <span class="text-slate-500">Customer notified</span>
              <span>{{ formatDate(store.current.customer_notified_at) }}</span>
            </li>
          </ul>
        </div>
      </div>

      <div class="mb-4 grid gap-4 xl:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Risk assessment</h2>
          <form class="space-y-3" @submit.prevent="onAssess">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Likelihood (1-5)</label>
                <input v-model.number="assessForm.risk_likelihood" type="number" min="1" max="5" class="input" required />
              </div>
              <div>
                <label class="mb-1 block text-xs font-medium text-slate-500">Impact (1-5)</label>
                <input v-model.number="assessForm.risk_impact" type="number" min="1" max="5" class="input" required />
              </div>
            </div>
            <textarea v-model="assessForm.impact_analysis" rows="3" class="input" placeholder="Impact analysis" />
            <textarea v-model="assessForm.risk_assessment_notes" rows="2" class="input" placeholder="Assessment notes" />
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Save assessment</button>
          </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Containment & recovery</h2>
          <form class="mb-4 space-y-2" @submit.prevent="onContain">
            <textarea v-model="containForm.containment_summary" rows="2" class="input" placeholder="Containment actions" required />
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Record containment</button>
          </form>
          <form class="space-y-2" @submit.prevent="onRecover">
            <textarea v-model="recoverForm.recovery_summary" rows="2" class="input" placeholder="Recovery actions" required />
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Record recovery</button>
          </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Root cause & lessons</h2>
          <form class="mb-4 space-y-2" @submit.prevent="onRootCause">
            <textarea v-model="rootCauseForm.root_cause" rows="3" class="input" placeholder="Root cause analysis" required />
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Save root cause</button>
          </form>
          <form class="space-y-2" @submit.prevent="onLessons">
            <textarea v-model="lessonsForm.lessons_learned" rows="3" class="input" placeholder="Lessons learned" required />
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Save lessons learned</button>
          </form>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
          <h2 class="mb-3 text-sm font-semibold text-slate-900">Send notification</h2>
          <form class="space-y-3" @submit.prevent="onNotify">
            <select v-model="notifyForm.notification_type" class="input" required>
              <option value="regulator">Regulator</option>
              <option value="customer">Customer</option>
              <option value="affected_user">Affected user</option>
              <option value="internal">Internal</option>
            </select>
            <input v-model="notifyForm.recipient" type="text" class="input" placeholder="Recipient" required />
            <input v-model="notifyForm.subject" type="text" class="input" placeholder="Subject" />
            <textarea v-model="notifyForm.message" rows="3" class="input" placeholder="Message" />
            <input
              v-if="notifyForm.notification_type === 'regulator'"
              v-model="notifyForm.regulator_reference"
              type="text"
              class="input"
              placeholder="Regulator reference"
            />
            <label class="flex items-center gap-2 text-sm text-slate-700">
              <input v-model="notifyForm.send_now" type="checkbox" class="rounded border-slate-300" />
              Send now
            </label>
            <button type="submit" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60" :disabled="store.saving">Create notification</button>
          </form>
          <button
            type="button"
            class="mt-4 w-full rounded-lg border border-emerald-300 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 disabled:opacity-60"
            :disabled="store.saving || store.current.status === 'closed'"
            @click="onClose"
          >
            Close incident
          </button>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-sm font-semibold text-slate-900">Timeline</h2>
        <BreachTimeline :timeline="store.timeline" :loading="false" />
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted, reactive } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
// import PageHeader from '@/components/ui/PageHeader.vue';
import BreachSeverityBadge from '@/modules/compliance/components/BreachSeverityBadge.vue';
import BreachStatusBadge from '@/modules/compliance/components/BreachStatusBadge.vue';
import BreachTimeline from '@/modules/compliance/components/BreachTimeline.vue';
import ComplianceSubnav from '@/modules/compliance/components/ComplianceSubnav.vue';
import { useDataBreachStore } from '@/modules/compliance/stores/breaches';

const route = useRoute();
const store = useDataBreachStore();

const assessForm = reactive({
  risk_likelihood: 3,
  risk_impact: 3,
  impact_analysis: '',
  risk_assessment_notes: '',
});
const containForm = reactive({ containment_summary: '' });
const recoverForm = reactive({ recovery_summary: '' });
const rootCauseForm = reactive({ root_cause: '' });
const lessonsForm = reactive({ lessons_learned: '' });
const notifyForm = reactive({
  notification_type: 'regulator',
  recipient: '',
  subject: '',
  message: '',
  regulator_reference: '',
  send_now: true,
  channel: 'email',
});

onMounted(async () => {
  await store.fetchBreach(route.params.id);
  await store.fetchTimeline(route.params.id);
  if (store.current?.risk_likelihood) assessForm.risk_likelihood = store.current.risk_likelihood;
  if (store.current?.risk_impact) assessForm.risk_impact = store.current.risk_impact;
  assessForm.impact_analysis = store.current?.impact_analysis || '';
  rootCauseForm.root_cause = store.current?.root_cause || '';
  lessonsForm.lessons_learned = store.current?.lessons_learned || '';
});

async function refresh() {
  await store.fetchBreach(route.params.id);
  await store.fetchTimeline(route.params.id);
}

async function onAssess() {
  await store.assess(route.params.id, { ...assessForm });
  await refresh();
}

async function onContain() {
  await store.contain(route.params.id, { ...containForm });
  containForm.containment_summary = '';
  await refresh();
}

async function onRecover() {
  await store.recover(route.params.id, { ...recoverForm });
  recoverForm.recovery_summary = '';
  await refresh();
}

async function onRootCause() {
  await store.rootCause(route.params.id, { ...rootCauseForm });
  await refresh();
}

async function onLessons() {
  await store.lessonsLearned(route.params.id, { ...lessonsForm });
  await refresh();
}

async function onNotify() {
  await store.createNotification(route.params.id, { ...notifyForm });
  notifyForm.recipient = '';
  notifyForm.subject = '';
  notifyForm.message = '';
  notifyForm.regulator_reference = '';
  await refresh();
}

async function onClose() {
  await store.close(route.params.id, { comments: 'Closed from incident details' });
  await refresh();
}

function formatDate(value) {
  if (!value) return '—';
  return new Date(value).toLocaleString();
}
</script>


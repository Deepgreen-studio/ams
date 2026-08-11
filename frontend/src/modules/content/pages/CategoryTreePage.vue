<template>
  <div>
    <Teleport defer to="#page-header-actions">
      <RouterLink
        :to="{ name: 'content.categories' }"
        class="rounded-[12px] border border-zinc-200 px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-zinc-50"
      >
        Category list
      </RouterLink>
    </Teleport>

    <ContentSubnav />

    <div
      v-if="taxonomy.error"
      class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
    >
      {{ taxonomy.error }}
    </div>

    <div class="rounded-[12px] bg-white p-5 ring-1 ring-zinc-100 sm:p-8">
      <CategoryTree
        :nodes="taxonomy.categoryTree"
        :loading="taxonomy.loading"
        @edit="goEdit"
        @delete="confirmDelete"
      />
    </div>

    <DeleteConfirmation
      :open="Boolean(pendingDelete)"
      title="Delete category"
      :message="`Soft delete ${pendingDelete?.name || 'this category'}? Child categories must be removed first.`"
      confirm-label="Delete"
      :loading="taxonomy.saving"
      @cancel="pendingDelete = null"
      @confirm="doDelete"
    />
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import DeleteConfirmation from '@/modules/users/components/DeleteConfirmation.vue';
import CategoryTree from '@/modules/content/components/CategoryTree.vue';
import ContentSubnav from '@/modules/content/components/ContentSubnav.vue';
import { useTaxonomyStore } from '@/modules/content/stores/taxonomy';

const taxonomy = useTaxonomyStore();
const router = useRouter();
const pendingDelete = ref(null);

onMounted(() => {
  taxonomy.fetchCategoryTree();
});

function goEdit() {
  router.push({ name: 'content.categories' });
}

function confirmDelete(node) {
  pendingDelete.value = node;
}

async function doDelete() {
  await taxonomy.deleteCategory(pendingDelete.value.uuid);
  pendingDelete.value = null;
  await taxonomy.fetchCategoryTree();
}
</script>

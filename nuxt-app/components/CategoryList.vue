<template>
  <div class="max-w-2xl w-full p-6 bg-white border rounded-2xl shadow">
    <h2 class="text-2xl font-semibold mb-4 text-gray-800">Category Tree</h2>

    <ul v-if="categories?.length" class="space-y-2">
      <CategoryTreeItem
        v-for="category in categories"
        :key="category.uid"
        :category="category"
      />
    </ul>
    <p v-else class="text-gray-500">No categories found.</p>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { sdk } from '../sdk'


const categories = ref([]);

onMounted(async () => {
  const result = await sdk.magento.categories({});

  const rootCategory = result?.data?.categories?.items?.[0];

  categories.value = rootCategory?.children ?? []
})
</script>

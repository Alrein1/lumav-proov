<template>
  <li>
    <div class="pl-2 border-l-2 border-blue-400">
      <p class="text-gray-800 font-medium">{{ category.name }}</p>
      <ul v-if="category.children?.length" class="ml-4 mt-2 space-y-1">
        <CategoryTreeItem v-for="child in category.children" :key="child.id" :category="child" />
      </ul>
    </div>
  </li>
</template>

<script setup>
  defineProps({ category: Object });
</script>

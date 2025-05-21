<template>
  <div class="min-h-screen bg-gray-100 p-6 flex flex-col items-center">
    <div class="mb-6 flex space-x-4">
      <button
        @click="changeWidget('weather')"
        :class="[
          'px-6 py-2 rounded-lg shadow transition',
          activeWidget === 'weather'
            ? 'bg-blue-600 text-white'
            : 'bg-white text-gray-800 border border-gray-300 hover:bg-gray-50'
        ]"
      >
        Weather
      </button>
      <button
        @click="changeWidget('categories')"
        :class="[
          'px-6 py-2 rounded-lg shadow transition',
          activeWidget === 'categories'
            ? 'bg-blue-600 text-white'
            : 'bg-white text-gray-800 border border-gray-300 hover:bg-gray-50'
        ]"
      >
        Categories
      </button>
    </div>

    <div class="w-full max-w-3xl">
      <div v-if="activeWidget === 'weather'" class="bg-white rounded-xl shadow p-6">
        <WeatherWidget />
      </div>
      <div v-if="activeWidget === 'categories'" class="bg-white rounded-xl shadow p-6">
        <CategoryList />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const activeWidget = ref('weather')

const changeWidget = (key) => {
  activeWidget.value = key
}
</script>

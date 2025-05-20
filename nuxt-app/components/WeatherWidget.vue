<template>
  <div class="flex flex-col items-center justify-start min-h-screen p-6 bg-gray-100">
    <div class="weather-widget flex flex-col gap-4 max-w-2xl w-full p-6 bg-white border rounded-2xl shadow">
      <p class="text-2xl font-semibold text-gray-800">Enter a city</p>
      <div class="flex gap-2">
        <input
          v-model="city"
          class="flex-1 border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="e.g. Tallinn"
        />
        <button
          @click="fetchWeather"
          class="bg-blue-600 text-white px-5 py-2 rounded-lg text-sm hover:bg-blue-700 transition"
        >
          Search
        </button>
      </div>
    </div>
    <div
      v-if="weather"
      :class="['flex items-center justify-center max-w-2xl w-full mt-6 min-h-[120px] border rounded-2xl shadow p-6', tempClass]"
    >
      <p class="text-3xl font-medium text-gray-800">
        Temp in {{ weather.name }}: {{ weather.main.temp }}°C
      </p>
    </div>
  </div>
</template>

<script setup>
    import { ref } from 'vue';
    import { useRuntimeConfig } from '#app';

    const config = useRuntimeConfig();
    const apiKey = config.public.OPENWEATHER_API_KEY;

    const city = ref('Tallinn');
    const weather = ref(null);

    const fetchWeather = async () => {
        try {
            const url = `https://api.openweathermap.org/data/2.5/weather?q=${city.value}&appid=${apiKey}&units=metric`;
            const res = await fetch(url);
            if (!res.ok) throw new Error('Unable to find city');
            const data = await res.json();
            weather.value = data;
        } catch (err) {
            console.log("");
        } 
    }

  const tempClass = computed(() => {
    if (!weather.value) return 'bg-gray-300';

    const temp = weather.value.main.temp;
    let category;

    if (temp <= -10) category = 'freezing';
    else if (temp <= 0) category = 'cold';
    else if (temp <= 10) category = 'chilly';
    else if (temp <= 18) category = 'mild';
    else if (temp <= 25) category = 'warm';
    else if (temp <= 32) category = 'hot';
    else category = 'scorching';

    switch (category) {
      case 'freezing':
        return 'bg-blue-900 text-white';
      case 'cold':
        return 'bg-blue-600 text-white';
      case 'chilly':
        return 'bg-cyan-500 text-white';
      case 'mild':
        return 'bg-green-400 text-white';
      case 'warm':
        return 'bg-yellow-300 text-black';
      case 'hot':
        return 'bg-orange-500 text-white';
      case 'scorching':
        return 'bg-red-600 text-white';
      default:
        return 'bg-gray-300';
  }
});
</script>
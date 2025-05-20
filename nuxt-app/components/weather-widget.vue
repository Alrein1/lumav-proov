<script setup>
    import { ref } from 'vue';
    import { useRuntimeConfig } from '#app';

    const config = useRuntimeConfig();
    const apiKey = config.public.OPENWEATHER_API_KEY;
    
    const city = ref('Tallinn');
    const weather = ref(null);

    const fetchWeather = async () => {
        try {
            const url = 'https://api.openweathermap.org/data/2.5/weather?q=${city.value}&appid=${apiKey}&units=metric';
            const res = await fetch(url);
            if (!res.ok) throw new Error('Unable to find city');
            const data = await res.json();
            weather.value = data;
        } catch (err) {
            console.log("");
        } 
    }
</script>
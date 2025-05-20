// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-05-15',
  devtools: { enabled: true },
  runtimeConfig: {
  public: {
    OPENWEATHER_API_KEY: process.env.NUXT_PUBLIC_OPENWEATHER_API_KEY
  }}
})

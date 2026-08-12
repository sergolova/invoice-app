import tailwindcss from '@tailwindcss/vite'

export default defineNuxtConfig({
  compatibilityDate: '2026-08-12',
  devtools: { enabled: true },
  modules: [],
  css: ['~/assets/css/main.css'],
  vite: {
    plugins: [
      tailwindcss(),
    ],
  },
  runtimeConfig: {
    apiBaseInternal: 'http://backend:8000/api', // default
    public: {
      apiBase: 'http://localhost:8000/api'      // default
    }
  }
})
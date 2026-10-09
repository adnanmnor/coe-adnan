export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  ssr: false,
  devtools: { enabled: true },
  runtimeConfig: {
    public: {
      apiBase: '/api',
    },
  },
  devServer: {
    host: '0.0.0.0',
    port: 3000,
  },
})
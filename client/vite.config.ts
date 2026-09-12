import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig, loadEnv } from 'vite'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  // По умолчанию — nginx из backend/compose.yaml.
  // Без Docker: VITE_PROXY_TARGET=http://localhost:8000 (см. composer serve).
  const target = env.VITE_PROXY_TARGET || 'http://localhost:80'

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    server: {
      proxy: {
        // Запросы к /api уходят на бэкенд, поэтому в браузере
        // это остаётся тем же origin — без CORS.
        '/api': {
          target,
          changeOrigin: true,
          configure: (proxy) => {
            proxy.on('error', (err) => {
              console.error(
                `[proxy] ${target} не отвечает — бэкенд запущен? (${err.message})`,
              )
            })
          },
        },
      },
    },
  }
})

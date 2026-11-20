import react from '@vitejs/plugin-react'
import { dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv } from 'vite'

const frontendDirectory = dirname(fileURLToPath(import.meta.url))

export default defineConfig(({ mode }) => {
  // La ruta de API y el destino de Laravel se configuran en frontend/.env.
  const env = loadEnv(mode, frontendDirectory, 'VITE_')
  const apiPath = env.VITE_API_URL
  const backendUrl = env.VITE_API_PROXY_TARGET

  if (!apiPath || !backendUrl) {
    throw new Error('Configura VITE_API_URL y VITE_API_PROXY_TARGET en frontend/.env')
  }

  return {
    plugins: [react()],
    server: {
      proxy: {
        [apiPath]: {
          target: backendUrl,
          changeOrigin: true,
        },
      },
    },
  }
})

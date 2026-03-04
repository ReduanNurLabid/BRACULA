import { defineConfig } from 'vite'
import devServer from '@hono/vite-dev-server'

export default defineConfig({
    server: {
        port: 3000,
    },
    plugins: [
        devServer({
            entry: 'src/index.ts',
        }),
    ],
})

import { defineConfig, loadEnv } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const backend = env.BACKEND_URL || 'http://127.0.0.1:8000';

    return {
        plugins: [vue()],
        // The browser talks only to this dev server; API calls and public invoice links go to Laravel.
        server: { port: 5173, proxy: { '/api': backend, '/i': backend } },
        preview: { port: 5173, proxy: { '/api': backend, '/i': backend } },
    };
});

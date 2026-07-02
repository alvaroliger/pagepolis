import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.tsx',
            refresh: true,
        }),
        react(),
    ],
    build: {
        rollupOptions: {
            output: {
                // CodeMirror and its @lezer parsers change only on package upgrades, not
                // on every editor code change. Isolating them in a stable vendor chunk
                // means returning users skip the ~400 kB re-download after each deploy.
                manualChunks(id) {
                    if (
                        id.includes('/node_modules/@codemirror/') ||
                        id.includes('/node_modules/codemirror/') ||
                        id.includes('/node_modules/@lezer/')
                    ) {
                        return 'vendor-codemirror';
                    }
                },
            },
        },
    },
});

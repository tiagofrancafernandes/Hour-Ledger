import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { fileURLToPath, URL } from 'node:url';
import VueDevTools from 'vite-plugin-vue-devtools';

// https://vite.dev/config/
export default defineConfig({
    plugins: [
        vue(),
        tailwindcss(),
        VueDevTools({
            launchEditor: 'vscode',
        }),
        //
    ],
    build: {
        outDir: process.env.BUILD_OUT_DIR || 'dist',
    },
    resolve: {
        alias: {
            '@@': path.resolve(__dirname, './'), // Mapeia '@' para o diretório 'src'
            // '@': path.resolve(__dirname, './src'), // Mapeia '@' para o diretório 'src'
            // '@components': path.resolve(__dirname, './src/components'), // Alias para o diretório de componentes
            // '@utils': path.resolve(__dirname, './src/utils'), // Alias para o diretório de utilitários

            '@': fileURLToPath(new URL('./src', import.meta.url)),
            '@assets': fileURLToPath(new URL('./src/assets', import.meta.url)),
            '@layouts': fileURLToPath(new URL('./src/layouts', import.meta.url)),
            '@types': fileURLToPath(new URL('./src/types', import.meta.url)),
            // '@components': fileURLToPath(new URL('./src/components', import.meta.url)),
            '@router': fileURLToPath(new URL('./src/router', import.meta.url)),
            '@composables': fileURLToPath(new URL('./src/composables', import.meta.url)),
            '@views': fileURLToPath(new URL('./src/views', import.meta.url)),
            '@services': fileURLToPath(new URL('./src/services', import.meta.url)),
            '@stores': fileURLToPath(new URL('./src/stores', import.meta.url)),
            '@plugins': fileURLToPath(new URL('./src/plugins', import.meta.url)),
            '@utils': fileURLToPath(new URL('./src/utils', import.meta.url)),
            '@public': fileURLToPath(new URL('./public', import.meta.url)),
        },
    },
    server: {
        // host: '0.0.0.0',
        host: true,
        // allowedHosts: [
        //     'app.tiagoapps.com.br',
        //     'app.local.tiagoapps.com.br',
        //     'app.hml.tiagoapps.com.br',
        //     // ... other hosts
        // ],
        allowedHosts: true, // Any host
        proxy: {
            '/__open-in-editor': {
                target: process.env.OPEN_IN_EDITOR_URL || 'http://host.docker.internal:3001',
                changeOrigin: true,
            },
        },
        fs: {
            strict: false,
        },
    },
});

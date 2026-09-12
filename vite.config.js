import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: true,
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
        },
    },
    // ルートやキャッシュの設定を明示して上位ディレクトリへのアクセスを防ぐ
    root: __dirname,
    cacheDir: resolve(__dirname, 'node_modules/.vite'),
});

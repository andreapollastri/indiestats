import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.scss', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // Bootstrap still relies on Sass @import / legacy APIs; silence until Bootstrap migrates.
    // https://getbootstrap.com/docs/5.3/getting-started/vite/
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: [
                    'import',
                    'mixed-decls',
                    'color-functions',
                    'global-builtin',
                    'if-function',
                ],
            },
        },
    },
});

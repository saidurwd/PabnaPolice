import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Only the admin panel is bundled. The public site keeps its original theme files in public/assets
// (old jQuery plugins that rely on globals and relative url() paths), served with a ?v= cache-buster.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/admin.css', 'resources/js/admin.js'],
            refresh: true,
        }),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

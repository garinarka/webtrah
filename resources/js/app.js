import '../css/app.css'
import './bootstrap'

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { ZiggyVue } from 'ziggy-js'
import { createPinia } from 'pinia'

const pinia = createPinia()

const appName = import.meta.env.VITE_APP_NAME || 'Trah Keluarga'

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            // Tanpa argumen kedua: ZiggyVue otomatis pakai window.Ziggy
            // yang sudah di-inject server-side lewat @routes di
            // app.blade.php. Argumen {route} sebelumnya SALAH — bikin
            // route() versi mixin (dipakai kalau route() dipanggil di
            // dalam <template>) dapat config yang rusak.
            .use(ZiggyVue)
            .mount(el)
    },
    progress: {
        color: '#4B5563',
    },
})

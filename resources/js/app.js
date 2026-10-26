import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import ToastDeNotificacao from './Componentes/ToastDeNotificacao.vue'

createInertiaApp({
    resolve: (name) => {
        const paginas = import.meta.glob('./Paginas/**/*.vue', { eager: true })
        return paginas[`./Paginas/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h('div', { class: 'contents' }, [h(App, props), h(ToastDeNotificacao)]) }).use(plugin).mount(el)
    },
})

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'))
}

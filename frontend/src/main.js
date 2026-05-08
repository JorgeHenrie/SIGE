import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import roteador from './router/index.js'

const app = createApp(App)

app.use(createPinia())
app.use(roteador)

app.mount('#app')

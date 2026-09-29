import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router';
import { installAppTimezone } from '@/utils/appTimezone';
import './assets/app.css';

installAppTimezone();

const app = createApp(App);

app.use(createPinia());
app.use(router);
app.mount('#app');

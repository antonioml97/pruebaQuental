import { createApp } from 'vue';
import App from './App.vue';
import { createAppRouter } from './router';

const app = createApp(App);
const router = createAppRouter();

app.use(router);
router.isReady().then(() => app.mount('#app'));

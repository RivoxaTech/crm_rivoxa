import './css/app.css';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// show nothing until the first route (and the login check) has resolved, so the sidebar never flashes before the login page
const app = createApp(App).use(router);
router.isReady().then(() => app.mount('#app'));

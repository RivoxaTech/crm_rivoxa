import { createRouter, createWebHistory } from 'vue-router';
import { api } from './api';
import { state } from './store';

const page = (name) => () => import(`./pages/${name}.vue`);

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior: () => ({ top: 0 }),
    routes: [
        { path: '/login', name: 'login', component: page('Login') },
        { path: '/', name: 'dashboard', component: page('Dashboard') },
        { path: '/clients', component: page('Clients') },
        { path: '/clients/:id', component: page('ClientDetail') },
        { path: '/projects', component: page('Projects') },
        { path: '/projects/new', component: page('ProjectForm') },
        { path: '/projects/:id', component: page('ProjectDetail') },
        { path: '/projects/:id/edit', component: page('ProjectForm') },
        { path: '/invoices', component: page('Invoices') },
        { path: '/account', component: page('Account') },
        { path: '/partners', component: page('Partners') },
        { path: '/partners/:id', component: page('PartnerDetail') },
        { path: '/renewals', component: page('Renewals') },
        { path: '/services', component: page('Services') },
        { path: '/settings', component: page('Settings') },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
});

router.beforeEach(async (to) => {
    if (!state.ready) {
        try {
            state.user = (await api.get('/me')).data;
            await refreshShared();
        } catch {
            state.user = null;
        }
        state.ready = true;
    }
    if (!state.user && to.name !== 'login') return '/login';
    if (state.user && to.name === 'login') return '/';
});

/** Settings and partner names are needed on most screens. */
export async function refreshShared() {
    const [s, p] = await Promise.all([api.get('/settings'), api.get('/partners')]);
    state.settings = s.data.settings;
    state.partners = p.data;
}

export default router;

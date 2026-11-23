<script setup>
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Icon from './components/Icon.vue';
import Toasts from './components/Toasts.vue';
import ConfirmHost from './components/ConfirmHost.vue';
import { api } from './api';
import { state } from './store';

const logo = '/images/rivoxa-logo.png'; // served from /public, so keep it out of Vite's module graph
const route = useRoute();
const router = useRouter();
const open = ref(false);
const bare = computed(() => route.name === 'login');
watch(() => route.fullPath, () => (open.value = false));

const nav = [
    { to: '/', icon: 'dashboard', label: 'Dashboard' },
    { group: 'Work' },
    { to: '/clients', icon: 'users', label: 'Clients' },
    { to: '/projects', icon: 'briefcase', label: 'Projects' },
    { to: '/invoices', icon: 'file', label: 'Invoices' },
    { to: '/renewals', icon: 'globe', label: 'Domains & hosting' },
    { group: 'Money' },
    { to: '/account', icon: 'wallet', label: 'Company account' },
    { to: '/partners', icon: 'pie', label: 'Partners' },
    { group: 'Setup' },
    { to: '/services', icon: 'tag', label: 'Services & prices' },
    { to: '/settings', icon: 'sliders', label: 'Settings' },
];

async function logout() {
    await api.post('/logout');
    state.user = null;
    state.ready = false;
    router.push('/login');
}
</script>

<template>
    <router-view v-if="bare" />
    <div v-else class="shell">
        <div class="topbar"><button aria-label="Menu" @click="open = !open"><Icon name="menu" :size="22" /></button><img :src="logo" alt="Rivoxa" /></div>
        <aside class="side" :class="{ open }">
            <div class="logo"><img :src="logo" alt="Rivoxa — Building Intelligent Solutions" /></div>
            <nav class="nav">
                <template v-for="(n, i) in nav" :key="i">
                    <div v-if="n.group" class="group">{{ n.group }}</div>
                    <router-link v-else :to="n.to" :exact-active-class="n.to === '/' ? 'router-link-active' : ''" :active-class="n.to === '/' ? '' : 'router-link-active'"><Icon :name="n.icon" /> {{ n.label }}</router-link>
                </template>
            </nav>
            <div class="foot">
                <div class="who"><b>{{ state.user?.name }}</b><span class="small">{{ state.user?.email }}</span></div>
                <button title="Sign out" @click="logout"><Icon name="logout" /></button>
            </div>
        </aside>
        <main class="main"><router-view :key="route.fullPath" /></main>
    </div>
    <Toasts />
    <ConfirmHost />
</template>

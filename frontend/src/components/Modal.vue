<script setup>
import { onMounted, onUnmounted } from 'vue';
import Icon from './Icon.vue';

defineProps({ title: String, wide: Boolean });
const emit = defineEmits(['close']);
const onKey = (e) => e.key === 'Escape' && emit('close');

onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <div class="modal-bg" @mousedown.self="emit('close')">
            <div class="modal" :class="{ wide }" role="dialog" aria-modal="true">
                <div class="modal-head">
                    <h3>{{ title }}</h3>
                    <button class="btn btn-ghost btn-icon" aria-label="Close" @click="emit('close')"><Icon name="x" /></button>
                </div>
                <div class="modal-body"><slot /></div>
                <div v-if="$slots.footer" class="modal-foot"><slot name="footer" /></div>
            </div>
        </div>
    </Teleport>
</template>

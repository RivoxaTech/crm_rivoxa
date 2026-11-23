<script setup>
import { computed } from 'vue';
import { fmt, short } from '../utils';

const props = defineProps({ items: Array, size: { type: Number, default: 150 } });
const total = computed(() => props.items.reduce((a, i) => a + i.value, 0));
const r = computed(() => props.size / 2 - 14);
const circ = computed(() => 2 * Math.PI * r.value);
const arcs = computed(() => {
    let acc = 0;

    return props.items.map((i) => {
        const len = total.value ? (i.value / total.value) * circ.value : 0;
        const a = { ...i, len, offset: -acc };
        acc += len;

        return a;
    });
});
</script>

<template>
    <div class="donut-wrap">
        <svg :width="size" :height="size" role="img">
            <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" stroke="#eceef1" stroke-width="18" />
            <circle v-for="a in arcs" :key="a.label" :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke="a.color" stroke-width="18" :stroke-dasharray="`${Math.max(a.len - 2, 0)} ${circ}`" :stroke-dashoffset="a.offset" :transform="`rotate(-90 ${size / 2} ${size / 2})`" />
            <text :x="size / 2" :y="size / 2 - 2" text-anchor="middle" style="font: 700 17px Montserrat; fill: #141414">{{ short(total) }}</text>
            <text :x="size / 2" :y="size / 2 + 15" text-anchor="middle">billed</text>
        </svg>
        <div class="donut-list">
            <div v-for="a in arcs" :key="a.label">
                <i :style="{ background: a.color }"></i><span class="grow">{{ a.label }}</span>
                <span class="mono strong">{{ fmt(a.value) }}</span><span class="muted small" style="width: 38px; text-align: right">{{ total ? Math.round((a.value / total) * 100) : 0 }}%</span>
            </div>
        </div>
    </div>
</template>

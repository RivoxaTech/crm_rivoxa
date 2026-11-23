<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { fmt, short } from '../utils';

const props = defineProps({ labels: Array, series: Array, height: { type: Number, default: 260 } });

const box = ref(null);
const w = ref(640);
const hover = ref(-1);
let ro;
onMounted(() => {
    w.value = box.value.clientWidth || 640;
    ro = new ResizeObserver(() => (w.value = box.value.clientWidth || 640));
    ro.observe(box.value);
});
onBeforeUnmount(() => ro?.disconnect());

const pad = { l: 46, r: 6, t: 10, b: 26 };
const step = computed(() => {
    const raw = Math.max(1, ...props.series.flatMap((s) => s.values)) / 4;
    const p = 10 ** Math.floor(Math.log10(raw));
    const n = raw / p;

    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * p;
});
const ticks = computed(() => [0, 1, 2, 3, 4].map((i) => i * step.value));
const innerW = computed(() => w.value - pad.l - pad.r);
const innerH = computed(() => props.height - pad.t - pad.b);
const y = (v) => pad.t + innerH.value - (v / (step.value * 4)) * innerH.value;
const group = computed(() => innerW.value / props.labels.length);
const barW = computed(() => Math.min(20, (group.value * 0.72) / props.series.length));
const x0 = (i, s) => pad.l + i * group.value + group.value / 2 - (props.series.length * barW.value + (props.series.length - 1) * 3) / 2 + s * (barW.value + 3);
</script>

<template>
    <div ref="box" class="chart">
        <svg :width="w" :height="height" role="img">
            <g v-for="t in ticks" :key="t">
                <line :x1="pad.l" :x2="w - pad.r" :y1="y(t)" :y2="y(t)" stroke="#eceef1" />
                <text :x="pad.l - 8" :y="y(t) + 4" text-anchor="end">{{ short(t) }}</text>
            </g>
            <rect v-if="hover >= 0" :x="pad.l + hover * group" :y="pad.t" :width="group" :height="innerH" fill="#f4f5f7" rx="6" />
            <g v-for="(s, si) in series" :key="s.name">
                <rect v-for="(v, i) in s.values" :key="i" :x="x0(i, si)" :y="y(v)" :width="barW" :height="Math.max(innerH + pad.t - y(v), v > 0 ? 2 : 0)" :fill="s.color" rx="3" />
            </g>
            <text v-for="(l, i) in labels" :key="l + i" :x="pad.l + i * group + group / 2" :y="height - 6" text-anchor="middle">{{ l }}</text>
            <rect v-for="(l, i) in labels" :key="'h' + i" :x="pad.l + i * group" :y="pad.t" :width="group" :height="innerH" fill="transparent" @mouseenter="hover = i" @mouseleave="hover = -1" />
        </svg>
        <div v-if="hover >= 0" class="tip" :style="{ left: pad.l + hover * group + group / 2 + 'px', top: pad.t + 'px' }">
            <b>{{ labels[hover] }}</b>
            <div v-for="s in series" :key="s.name"><i :style="{ background: s.color }"></i>{{ s.name }}: Rs {{ fmt(s.values[hover]) }}</div>
        </div>
    </div>
</template>
